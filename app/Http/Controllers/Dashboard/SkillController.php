<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\SkillRequest;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    /**
     * List the skills.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard/Skills', [
            'skills' => Skill::query()
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store a new skill.
     */
    public function store(SkillRequest $request): RedirectResponse
    {
        Skill::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Skill added.')]);

        return to_route('dashboard.skills.index');
    }

    /**
     * Update a skill.
     */
    public function update(SkillRequest $request, Skill $skill): RedirectResponse
    {
        $skill->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Skill updated.')]);

        return to_route('dashboard.skills.index');
    }

    /**
     * Delete a skill.
     */
    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Skill deleted.')]);

        return to_route('dashboard.skills.index');
    }
}
