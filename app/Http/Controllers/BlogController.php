<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BlogController extends Controller
{
    /**
     * The public blog index: published posts, newest first.
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('posts/Index', [
            'posts' => Post::query()
                ->published()
                ->orderByDesc('published_at')
                ->get()
                ->each(fn (Post $post) => $post->teaser_text = $post->teaser()),
        ]);
    }

    /**
     * A single published post, rendered from Markdown.
     */
    public function show(Post $post): InertiaResponse
    {
        abort_unless(
            $post->published_at !== null && $post->published_at->lte(now()),
            Response::HTTP_NOT_FOUND,
        );

        return Inertia::render('posts/Show', [
            'post' => tap($post, fn (Post $p) => $p->body_html = $p->bodyHtml()),
            'recent' => Post::query()
                ->published()
                ->whereKeyNot($post->getKey())
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(['id', 'slug', 'title']),
        ]);
    }
}
