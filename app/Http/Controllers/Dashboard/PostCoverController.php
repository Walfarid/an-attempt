<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UploadCoverRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PostCoverController extends Controller
{
    /**
     * Upload (or replace) the cover image of a post.
     */
    public function update(UploadCoverRequest $request, Post $post): RedirectResponse
    {
        $this->deleteCover($post);

        $path = $request->file('cover')->store("posts/{$post->id}", 'media');

        $post->update(['cover_image_path' => $path]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cover updated.')]);

        return to_route('dashboard.posts.index');
    }

    /**
     * Remove the cover image of a post.
     */
    public function destroy(Post $post): RedirectResponse
    {
        $this->deleteCover($post);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cover removed.')]);

        return to_route('dashboard.posts.index');
    }

    /**
     * Delete the current cover file, if any, and clear the column.
     */
    private function deleteCover(Post $post): void
    {
        if ($post->cover_image_path !== null) {
            Storage::disk('media')->delete($post->cover_image_path);
        }

        $post->forceFill(['cover_image_path' => null])->save();
    }
}
