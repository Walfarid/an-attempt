<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\GuideRequest;
use App\Models\Guide;
use App\Models\Post;
use App\Support\SitemapCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    /**
     * List the guides (body excluded for performance; load via show() when editing),
     * plus every post title for the related-posts picker.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard/Guides', [
            'guides' => Guide::query()
                ->select(['id', 'slug', 'title', 'cover_image_path', 'estimated_time', 'published_at'])
                ->latest()
                ->get()
                ->each(function (Guide $guide): void {
                    $guide->append('cover_url')->makeHidden(['cover_image_path']);
                }),
            // Editor suggestions: every post ever written, one extra query.
            'postTitles' => Post::query()->select(['id', 'title'])->orderBy('title')->get(),
        ]);
    }

    /**
     * Lazy-load a single guide with body and linked post IDs for editing.
     *
     * @return array{id: int, slug: string, title: string, body: string|null, teaser: string|null, prerequisites: string|null, estimated_time: string|null, cover_url: string|null, published_at: string|null, posts: array<int, int>}
     */
    public function show(Guide $guide): array
    {
        /** @var list<int> $guidePosts */
        $guidePosts = $guide->posts()->orderBy('title')->pluck('posts.id')->all();

        return [
            'id' => $guide->id,
            'slug' => $guide->slug,
            'title' => $guide->title,
            'body' => $guide->body,
            'teaser' => $guide->teaser,
            'prerequisites' => $guide->prerequisites,
            'estimated_time' => $guide->estimated_time,
            'cover_url' => $guide->cover_url,
            'published_at' => $guide->published_at?->toIso8601String(),
            'posts' => $guidePosts,
        ];
    }

    /**
     * Store a new guide.
     */
    public function store(GuideRequest $request): RedirectResponse
    {
        $guide = Guide::create($request->validated());

        $guide->posts()->sync($request->validated('posts', []));

        SitemapCache::invalidate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Guide added.')]);

        return to_route('dashboard.guides.index');
    }

    /**
     * Update a guide.
     */
    public function update(GuideRequest $request, Guide $guide): RedirectResponse
    {
        $guide->update($request->validated());

        $guide->posts()->sync($request->validated('posts', []));

        SitemapCache::invalidate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Guide updated.')]);

        return to_route('dashboard.guides.index');
    }

    /**
     * Delete a guide along with its cover image file.
     */
    public function destroy(Guide $guide): RedirectResponse
    {
        if ($guide->cover_image_path !== null) {
            Storage::disk('media')->delete($guide->cover_image_path);
        }

        $guide->delete();

        SitemapCache::invalidate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Guide deleted.')]);

        return to_route('dashboard.guides.index');
    }
}
