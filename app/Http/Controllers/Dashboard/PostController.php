<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\PostRequest;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            // Editor suggestions: every tag name ever used, one extra query.
            'tagNames' => Tag::query()->orderBy('name')->pluck('name')->all(),
        ]);
    }

    /**
     * Lazy-load a single post with body and tags for editing.
     *
     * @return array{id: int, slug: string, title: string, excerpt: string|null, body: string|null, cover_url: string|null, published_at: string|null, tags: list<string>}
     */
    public function show(Post $post): array
    {
        /** @var list<string> $tagNames */
        $tagNames = $post->tags()->orderBy('name')->pluck('name')->all();

        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'body' => $post->body,
            'cover_url' => $post->cover_url,
            'published_at' => $post->published_at?->toIso8601String(),
            'tags' => $tagNames,
        ];
    }

    /**
     * Store a new post.
     */
    public function store(PostRequest $request): RedirectResponse
    {
        $post = Post::create($request->validated());

        $this->syncTags($post, $request->validated('tags', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post added.')]);

        return to_route('dashboard.posts.index');
    }

    /**
     * Update a post.
     */
    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->validated());

        $this->syncTags($post, $request->validated('tags', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post updated.')]);

        return to_route('dashboard.posts.index');
    }

    /**
     * Attach the validated tag names, creating tags that do not exist
     * yet. Wrapped in a transaction so a post is never left with a
     * half-synced tag set; detach-attach keeps the mapping exact.
     *
     * @param  list<string>  $names
     */
    private function syncTags(Post $post, array $names): void
    {
        if ($names === []) {
            $post->tags()->detach();

            return;
        }

        $tagIds = DB::transaction(function () use ($names): array {
            foreach ($names as $name) {
                Tag::firstOrCreate(
                    ['name' => $name],
                    ['slug' => $this->uniqueTagSlug(Str::slug($name))],
                );
            }

            /** @var list<int> */
            return Tag::whereIn('name', $names)->pluck('id')->all();
        });

        $post->tags()->sync($tagIds);
    }

    /**
     * A tag slug that is unique among existing tags: slug collisions
     * are kept impossible so /posts/tag/{slug} stays unambiguous.
     */
    private function uniqueTagSlug(string $base): string
    {
        $slug = $base !== '' ? $base : Str::random(8);
        $suffix = 0;

        while (Tag::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
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
