<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreScreenshotRequest;
use App\Models\Project;
use App\Models\ProjectScreenshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ScreenshotController extends Controller
{
    /**
     * Upload a screenshot for a project.
     */
    public function store(StoreScreenshotRequest $request, Project $project): RedirectResponse
    {
        $path = $request->file('image')->store("projects/{$project->id}", 'media');

        $project->screenshots()->create([
            'path' => $path,
            'alt' => $request->input('alt'),
            'sort_order' => ($project->screenshots()->max('sort_order') ?? 0) + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Screenshot uploaded.')]);

        return to_route('dashboard.projects.index');
    }

    /**
     * Delete a screenshot and its file.
     */
    public function destroy(Project $project, ProjectScreenshot $screenshot): RedirectResponse
    {
        Storage::disk('media')->delete($screenshot->path);
        $screenshot->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Screenshot deleted.')]);

        return to_route('dashboard.projects.index');
    }
}
