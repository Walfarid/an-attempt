<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Facades\Head;
use Laravel\Head\Facades\Schema;

class GuideController extends Controller
{
    /**
     * The public guides index: published guides, newest first.
     */
    public function index(Request $request): Response
    {
        $guides = Guide::query()
            ->select(['id', 'slug', 'title', 'teaser', 'cover_image_path', 'estimated_time', 'published_at'])
            ->published()
            ->orderByDesc('published_at')
            ->simplePaginate(12, ['*'], 'page')
            ->through(function (Guide $guide): Guide {
                $guide->append('cover_url')->makeHidden(['cover_image_path']);

                return $guide;
            });

        $lastModified = Guide::query()
            ->published()
            ->max('updated_at');

        if ($lastModified !== null && $this->isNotModified($request, Carbon::parse($lastModified))) {
            abort(304);
        }

        $request->attributes->set('last_modified', $lastModified !== null ? Carbon::parse($lastModified) : null);

        return Inertia::render('guides/Index', [
            // Inertia::scroll() registers the prop in page.scrollProps so the
            // client <InfiniteScroll data="guides"> can read its metadata and
            // reset/merge correctly on paginated visits.
            'guides' => Inertia::scroll($guides),
        ]);
    }

    /**
     * A single published guide, rendered from Markdown.
     *
     * The related posts for the "In this guide" section are eager loaded on
     * the same query. The route binding is intentionally unused (a manual
     * slug lookup keeps the page at a single round trip).
     */
    public function show(Request $request): Response
    {
        $slug = $request->route('guide');

        $guide = Guide::query()
            ->where('slug', $slug)
            // id is required for the posts eager load to match pivot rows;
            // makeHidden below keeps it off the public wire.
            ->select(['id', 'title', 'slug', 'body', 'teaser', 'prerequisites', 'estimated_time', 'cover_image_path', 'published_at', 'updated_at'])
            ->with(['posts' => fn ($query) => $query
                ->select(['posts.id', 'posts.slug', 'posts.title', 'posts.published_at'])
                ->published()
                ->orderByDesc('posts.published_at')
                ->limit(5)])
            ->firstOrFail();

        abort_unless(
            $guide->published_at !== null && $guide->published_at->lte(now()),
            \Illuminate\Http\Response::HTTP_NOT_FOUND,
        );

        // Conditional request: if the browser has a fresh copy, skip the
        // Markdown render and Inertia serialization entirely.
        if ($guide->updated_at !== null && $this->isNotModified($request, $guide->updated_at)) {
            abort(304);
        }

        Head::title($guide->title)
            ->description($guide->teaser ?? strip_tags($guide->bodyHtml()))
            ->og(type: OgType::Article, image: $guide->cover_url ?? url('/og-default.png'))
            ->canonical();

        Head::schema(
            Schema::article()
                ->headline($guide->title)
                ->description($guide->teaser ?? strip_tags($guide->bodyHtml()))
                ->publishedAt($guide->published_at)
                ->modifiedAt($guide->updated_at ?? now())
                ->image($guide->cover_url ?? url('/og-default.png'))
                ->set('mainEntityOfPage', $request->url())
        );

        $response = Inertia::render('guides/Show', [
            'guide' => tap($guide, function (Guide $g): void {
                $g->append('cover_url');
                $g->body_html = $g->bodyHtml();
                // id (pivot matching) stays off the public wire, per the
                // no-unused-IDs rule for public payloads.
                $g->makeHidden(['id', 'body', 'cover_image_path', 'updated_at']);
            }),
        ]);

        // Pass the timestamp via request attribute so the CachePublicResponses
        // middleware can promote it to a Last-Modified header on the Symfony
        // response (Inertia's Response has no headers of its own).
        $request->attributes->set('last_modified', $guide->updated_at);

        return $response;
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
