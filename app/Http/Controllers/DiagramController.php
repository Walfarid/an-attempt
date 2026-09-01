<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class DiagramController extends Controller
{
    /**
     * Stream the interactive diagram HTML attached to a post.
     *
     * The diagram is embedded via <iframe src="/diagrams/{slug}">, so
     * this endpoint must stay public and cacheable. 404s when the post
     * has no diagram attached, or when the stored file is missing.
     */
    public function show(Request $request, string $diagram): Response
    {
        $post = Post::query()
            ->where('slug', $diagram)
            ->select(['id', 'diagram_path', 'updated_at'])
            ->firstOrFail();

        if ($post->diagram_path === null) {
            abort(404, 'This post has no interactive diagram.');
        }

        $html = Storage::disk('media')->get($post->diagram_path);

        if ($html === null) {
            abort(404, 'The diagram file is missing.');
        }

        $request->attributes->set('last_modified', $post->updated_at);

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
