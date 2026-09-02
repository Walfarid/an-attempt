<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Profile;
use App\Models\Tag;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Facades\Head;
use Laravel\Head\Facades\Schema;

class BlogController extends Controller
{
    /**
     * The public blog index: published posts, newest first.
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('posts/Index', [
            // Inertia::scroll() registers the prop in page.scrollProps so the
            // client <InfiniteScroll data="posts"> can read its metadata and
            // reset/merge correctly on paginated visits.
            'posts' => Inertia::scroll(
                Post::query()
                    ->select(['id', 'slug', 'title', 'excerpt', 'cover_image_path', 'published_at'])
                    ->selectRaw('SUBSTRING(body, 1, 300) as body_preview')
                    ->with(['tags' => fn ($query) => $query->select(['tags.id', 'tags.slug', 'tags.name'])])
                    ->published()
                    ->orderByDesc('published_at')
                    ->simplePaginate(10, ['*'], 'page')
                    ->through(function (Post $post): Post {
                        $post->teaser_text = $post->teaser();
                        $post->append('cover_url')->makeHidden(['excerpt', 'body_preview', 'cover_image_path']);

                        return $post;
                    })
            ),
        ]);
    }

    /**
     * The recent-posts aggregate for the post page.
     *
     * Both supported drivers produce the same JSON array of
     * {id, slug, title} rows; only the aggregate function differs.
     *
     * @return literal-string
     */
    private function recentSql(string $driver): string
    {
        $aggregate = $driver === 'sqlite'
            ? "json_group_array(json_object('id', id, 'slug', slug, 'title', title))"
            : "COALESCE(JSON_ARRAYAGG(JSON_OBJECT('id', id, 'slug', slug, 'title', title)), JSON_ARRAY())";

        return "
            (SELECT {$aggregate}
             FROM (SELECT id, slug, title FROM posts
                   WHERE published_at IS NOT NULL AND published_at <= ?
                     AND slug <> ?
                   ORDER BY published_at DESC
                   LIMIT 3) r) AS recent_json
        ";
    }

    /**
     * A single published post, rendered from Markdown.
     *
     * The post and the "Keep reading" list are fetched in one query: the
     * recent posts are inlined into the post SELECT as a JSON array. The
     * route binding is intentionally unused (a manual slug lookup plus the
     * scalar subquery keeps the page at a single round trip).
     *
     * Returns 304 Not Modified when the browser's cached copy is still
     * fresh (If-Modified-Since matches the post's updated_at), so repeat
     * visits transfer zero bytes.
     */
    public function show(Request $request): InertiaResponse
    {
        $slug = $request->route('post');

        $post = Post::query()
            ->where('slug', $slug)
            // id is required for the tags eager load to match pivot rows;
            // makeHidden below keeps it off the public wire.
            ->select(['id', 'title', 'body', 'excerpt', 'cover_image_path', 'published_at', 'updated_at'])
            ->selectRaw(
                $this->recentSql(DB::connection()->getDriverName()),
                [now(), $slug],
            )
            ->with(['tags' => fn ($query) => $query->select(['tags.id', 'tags.slug', 'tags.name'])])
            ->firstOrFail();

        abort_unless(
            $post->published_at !== null && $post->published_at->lte(now()),
            Response::HTTP_NOT_FOUND,
        );

        // Conditional request: if the browser has a fresh copy, skip the
        // Markdown render and Inertia serialization entirely.
        if ($this->isNotModified($request, $post->updated_at)) {
            abort(304);
        }

        Head::title($post->title)
            ->description($post->excerpt ?? $post->teaser(25))
            ->og(type: OgType::Article, image: $post->cover_url ?? url('/og-default.png'))
            ->canonical();

        $authorName = Cache::remember('profile.name', now()->addHour(), fn (): string => Profile::query()->value('name') ?? 'Walfa');

        Head::schema(
            Schema::blogPosting()
                ->headline($post->title)
                ->description($post->excerpt ?? $post->teaser(25))
                ->publishedAt($post->published_at)
                ->modifiedAt($post->updated_at)
                ->author(Schema::person()->name($authorName))
                ->image($post->cover_url ?? url('/og-default.png'))
                ->set('mainEntityOfPage', $request->url())
                ->set('keywords', $post->tags->pluck('name')->all())
        );

        /** @var list<array{id: int, slug: string, title: string}> $recent */
        $recent = json_decode($post->recent_json, true, 512, JSON_THROW_ON_ERROR);

        $response = Inertia::render('posts/Show', [
            'post' => tap($post, function (Post $p): void {
                $p->append('cover_url');
                $p->body_html = $p->bodyHtml();
                // id (pivot matching) stays off the public wire, per the
                // no-unused-IDs rule for public payloads.
                $p->makeHidden(['id', 'body', 'cover_image_path', 'recent_json', 'excerpt', 'updated_at']);
            }),
            'recent' => $recent,
        ]);

        // Pass the timestamp via request attribute so the CachePublicResponses
        // middleware can promote it to a Last-Modified header on the Symfony
        // response (Inertia's Response has no headers of its own).
        $request->attributes->set('last_modified', $post->updated_at);

        return $response;
    }

    /**
     * A tag landing page: the published posts carrying this tag,
     * newest first, with the same teaser shape as the blog index.
     */
    public function tag(Request $request): InertiaResponse
    {
        $tag = Tag::query()
            ->where('slug', (string) $request->route('tag'))
            ->firstOrFail();

        Head::title('Posts tagged "'.$tag->name.'"')
            ->description('Articles tagged '.$tag->name.' — writing on software development, APIs, and deployment platforms.')
            ->canonical();

        return Inertia::render('posts/Tag', [
            'tag' => ['id' => $tag->id, 'slug' => $tag->slug, 'name' => $tag->name],
            'posts' => $tag->posts()
                // posts.id is required for the tags eager load to match
                // pivot rows; makeHidden below keeps it off the wire.
                ->select(['posts.id', 'posts.slug', 'posts.title', 'posts.excerpt', 'posts.cover_image_path', 'posts.published_at'])
                ->selectRaw('SUBSTRING(posts.body, 1, 300) as body_preview')
                ->with(['tags' => fn ($query) => $query->select(['tags.id', 'tags.slug', 'tags.name'])])
                ->published()
                ->orderByDesc('posts.published_at')
                ->get()
                ->each(function (Post $post): void {
                    $post->teaser_text = $post->teaser();
                    $post->append('cover_url');
                    // pivot and id stay off the public wire (the no-
                    // unused-IDs rule for public payloads).
                    $post->makeHidden(['id', 'cover_image_path', 'excerpt', 'body_preview', 'pivot']);
                }),
        ]);
    }

    /**
     * Check whether the request's If-Modified-Since header matches the
     * given timestamp (second precision, per HTTP spec).
     */
    private function isNotModified(Request $request, CarbonInterface $lastModified): bool
    {
        $since = $request->header('If-Modified-Since');

        if ($since === null) {
            return false;
        }

        return $lastModified->startOfSecond()->timestamp <= strtotime($since);
    }
}
