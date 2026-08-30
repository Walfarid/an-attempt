<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\PostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * List the posts (body excluded for performance; load via show() when editing).
     */
    public function index(): Response
    {
        return Inertia::render('dashboard/Posts', [
            'posts' => Post::query()
                ->select(['id', 'slug', 'title', 'cover_image_path', 'published_at'])
                ->latest()
                ->get()
                ->each(function (Post $post): void {
                    $post->append('cover_url')->makeHidden(['cover_image_path']);
                }),
        ]);
    }

    /**
     * Lazy-load a single post with body for editing.
     */
    public function show(Post $post): array
    {
        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'body' => $post->body,
            'cover_url' => $post->cover_url,
            'published_at' => $post->published_at?->toIso8601String(),
        ];
    }

    /**
     * Store a new post.
     */
    public function store(PostRequest $request): RedirectResponse
    {
        Post::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post added.')]);

        return to_route('dashboard.posts.index');
    }

    /**
     * Update a post.
     */
    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post updated.')]);

        return to_route('dashboard.posts.index');
    }

    /**
     * Delete a post along with its cover image file.
     */
    public function destroy(Post $post): RedirectResponse
    {
        if ($post->cover_image_path !== null) {
            Storage::disk('media')->delete($post->cover_image_path);
        }

        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post deleted.')]);

        return to_route('dashboard.posts.index');
    }
}
