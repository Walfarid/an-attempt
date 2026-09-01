<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UploadDiagramRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PostDiagramController extends Controller
{
    /**
     * Upload (or replace) the interactive diagram HTML of a post.
     */
    public function update(UploadDiagramRequest $request, Post $post): RedirectResponse
    {
        $this->deleteDiagram($post);

        $path = $request->file('diagram')->store("posts/{$post->id}", 'media');

        $post->update(['diagram_path' => $path]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Diagram updated.')]);

        return to_route('dashboard.posts.index');
    }

    /**
     * Remove the interactive diagram of a post.
     */
    public function destroy(Post $post): RedirectResponse
    {
        $this->deleteDiagram($post);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Diagram removed.')]);

        return to_route('dashboard.posts.index');
    }

    /**
     * Delete the current diagram file, if any, and clear the column.
     */
    private function deleteDiagram(Post $post): void
    {
        if ($post->diagram_path !== null) {
            Storage::disk('media')->delete($post->diagram_path);
        }

        $post->forceFill(['diagram_path' => null])->save();
    }
}
