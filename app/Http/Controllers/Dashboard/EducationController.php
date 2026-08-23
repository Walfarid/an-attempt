<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\EducationRequest;
use App\Models\Education;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EducationController extends Controller
{
    /**
     * List the education records.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard/Educations', [
            'educations' => Education::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * Store a new education record.
     */
    public function store(EducationRequest $request): RedirectResponse
    {
        Education::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Education added.')]);

        return to_route('dashboard.educations.index');
    }

    /**
     * Update an education record.
     */
    public function update(EducationRequest $request, Education $education): RedirectResponse
    {
        $education->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Education updated.')]);

        return to_route('dashboard.educations.index');
    }

    /**
     * Delete an education record.
     */
    public function destroy(Education $education): RedirectResponse
    {
        $education->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Education deleted.')]);

        return to_route('dashboard.educations.index');
    }
}
