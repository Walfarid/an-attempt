<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Facades\Head;

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
                    ->select(['id', 'slug', 'title', 'excerpt', 'published_at'])
                    ->selectRaw('SUBSTRING(body, 1, 300) as body_preview')
                    ->published()
                    ->orderByDesc('published_at')
                    ->simplePaginate(10, ['*'], 'page')
                    ->through(function (Post $post): Post {
                        $post->teaser_text = $post->teaser();
                        $post->makeHidden(['excerpt', 'body_preview']);

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
     */
    public function show(Request $request): InertiaResponse
    {
        $slug = $request->route('post');

        $post = Post::query()
            ->where('slug', $slug)
            ->select(['title', 'body', 'excerpt', 'cover_image_path', 'published_at'])
            ->selectRaw(
                $this->recentSql(DB::connection()->getDriverName()),
                [now(), $slug],
            )
            ->firstOrFail();

        abort_unless(
            $post->published_at !== null && $post->published_at->lte(now()),
            Response::HTTP_NOT_FOUND,
        );

        Head::title($post->title)
            ->description($post->excerpt ?? $post->teaser(25))
            ->og(type: OgType::Article)
            ->canonical();

        if ($post->cover_url) {
            Head::ogImage($post->cover_url);
        }

        /** @var list<array{id: int, slug: string, title: string}> $recent */
        $recent = json_decode($post->recent_json, true, 512, JSON_THROW_ON_ERROR);

        return Inertia::render('posts/Show', [
            'post' => tap($post, function (Post $p): void {
                $p->append('cover_url');
                $p->body_html = $p->bodyHtml();
                $p->makeHidden(['body', 'cover_image_path', 'recent_json', 'excerpt']);
            }),
            'recent' => $recent,
        ]);
    }
}
