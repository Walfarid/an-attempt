<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UploadGuideCoverRequest;
use App\Models\Guide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GuideCoverController extends Controller
{
    /**
     * Upload (or replace) the cover image of a guide.
     */
    public function update(UploadGuideCoverRequest $request, Guide $guide): RedirectResponse
    {
        $this->deleteCover($guide);

        $path = $request->file('cover')->store("guides/{$guide->id}", 'media');

        $guide->update(['cover_image_path' => $path]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cover updated.')]);

        return to_route('dashboard.guides.index');
    }

    /**
     * Remove the cover image of a guide.
     */
    public function destroy(Guide $guide): RedirectResponse
    {
        $this->deleteCover($guide);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cover removed.')]);

        return to_route('dashboard.guides.index');
    }

    /**
     * Delete the current cover file, if any, and clear the column.
     */
    private function deleteCover(Guide $guide): void
    {
        if ($guide->cover_image_path !== null) {
            Storage::disk('media')->delete($guide->cover_image_path);
        }

        $guide->forceFill(['cover_image_path' => null])->save();
    }
}
