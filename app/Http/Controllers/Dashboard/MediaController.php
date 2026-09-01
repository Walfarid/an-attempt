<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreMediaRequest;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    /**
     * Display a listing of media files.
     *
     * The editor's image picker fetches this endpoint directly (Accept:
     * application/json) and gets JSON; Inertia visits and plain browser
     * navigation get the dashboard page.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $media = Media::query()->latest()->get();

        if ($request->wantsJson()) {
            return response()->json(
                $media->map(fn ($m) => $this->serialize($m))
            );
        }

        return Inertia::render('dashboard/Media', [
            'media' => $media->map(fn ($m) => $this->serialize($m)),
        ]);
    }

    /**
     * Store a newly uploaded media file.
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('uploads', Str::uuid().".{$extension}", 'media');

        $media = Media::create([
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json($this->serialize($media), 201);
    }

    /**
     * Remove the specified media file.
     */
    public function destroy(Media $medium): RedirectResponse
    {
        Storage::disk('media')->delete($medium->path);
        $medium->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Media deleted.')]);

        return to_route('dashboard.media.index');
    }

    /**
     * Serialize a media model for JSON responses.
     *
     * @return array<string, mixed>
     */
    private function serialize(Media $media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->name,
            'url' => $media->url,
            'mime' => $media->mime,
            'size' => $media->size,
            'created_at' => $media->created_at,
        ];
    }
}
