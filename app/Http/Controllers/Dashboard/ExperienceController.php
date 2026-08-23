<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ExperienceRequest;
use App\Models\Experience;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExperienceController extends Controller
{
    /**
     * List the experiences.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard/Experience', [
            'experiences' => Experience::query()
                ->orderByDesc('started_at')
                ->get(),
        ]);
    }

    /**
     * Store a new experience.
     */
    public function store(ExperienceRequest $request): RedirectResponse
    {
        Experience::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Experience added.')]);

        return to_route('dashboard.experience.index');
    }

    /**
     * Update an experience.
     */
    public function update(ExperienceRequest $request, Experience $experience): RedirectResponse
    {
        $experience->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Experience updated.')]);

        return to_route('dashboard.experience.index');
    }

    /**
     * Delete an experience.
     */
    public function destroy(Experience $experience): RedirectResponse
    {
        $experience->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Experience deleted.')]);

        return to_route('dashboard.experience.index');
    }
}
