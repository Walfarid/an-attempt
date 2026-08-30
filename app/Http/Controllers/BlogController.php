<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;
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
            'posts' => Post::query()
                ->select(['id', 'slug', 'title', 'excerpt', 'published_at'])
                ->selectRaw('SUBSTRING(body, 1, 300) as body_preview')
                ->published()
                ->orderByDesc('published_at')
                ->simplePaginate(10, ['*'], 'page')
                ->through(function (Post $post): Post {
                    $post->teaser_text = $post->teaser();
                    $post->makeHidden(['excerpt']);

                    return $post;
                }),
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

        Head::title($post->title)
            ->description($post->excerpt ?? $post->teaser(25))
            ->og(type: OgType::Article)
            ->canonical();

        if ($post->cover_url) {
            Head::ogImage($post->cover_url);
        }

        return Inertia::render('posts/Show', [
            'post' => tap($post, function (Post $p): void {
                $p->append('cover_url');
                $p->body_html = $p->bodyHtml();
                $p->makeHidden(['body', 'cover_image_path', 'id', 'slug', 'excerpt', 'created_at', 'updated_at']);
            }),
            'recent' => Post::query()
                ->published()
                ->whereKeyNot($post->getKey())
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(['id', 'slug', 'title']),
        ]);
    }
}
