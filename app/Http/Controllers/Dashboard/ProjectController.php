<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ProjectRequest;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * List the projects.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard/Projects', [
            'projects' => Project::query()
                ->select(['id', 'title', 'description', 'year', 'live_url', 'repo_url', 'featured', 'published_at'])
                ->with(['skills' => fn ($q) => $q->select(['skills.id', 'skills.name', 'skills.category']), 'screenshots' => fn ($q) => $q->select(['id', 'project_id', 'path', 'alt'])])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->each(function (Project $project): void {
                    $project->screenshots->each->makeHidden(['project_id', 'path']);
                }),
            'skills' => Skill::query()
                ->select(['id', 'name', 'category'])
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store a new project.
     */
    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = Project::create($this->validated($request));
        $project->skills()->sync($request->input('skills', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project added.')]);

        return to_route('dashboard.projects.index');
    }

    /**
     * Update a project.
     */
    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($this->validated($request));
        $project->skills()->sync($request->input('skills', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return to_route('dashboard.projects.index');
    }

    /**
     * Delete a project along with its screenshot files.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $project->load('screenshots');
        Storage::disk('media')->delete($project->screenshots->pluck('path')->all());
        $project->screenshots()->delete();
        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted.')]);

        return to_route('dashboard.projects.index');
    }

    /**
     * The validated payload without the relationship data.
     *
     * @return array<string, mixed>
     */
    private function validated(ProjectRequest $request): array
    {
        return $request->safe()->except(['skills']);
    }
}
