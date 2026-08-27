<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\PublicationRequest;
use App\Models\Publication;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicationController extends Controller
{
    /**
     * List the publications.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard/Publications', [
            'publications' => Publication::query()
                ->select(['id', 'citation', 'venue', 'year', 'doi_url'])
                ->orderByDesc('year')
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * Store a new publication.
     */
    public function store(PublicationRequest $request): RedirectResponse
    {
        Publication::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Publication added.')]);

        return to_route('dashboard.publications.index');
    }

    /**
     * Update a publication.
     */
    public function update(PublicationRequest $request, Publication $publication): RedirectResponse
    {
        $publication->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Publication updated.')]);

        return to_route('dashboard.publications.index');
    }

    /**
     * Delete a publication.
     */
    public function destroy(Publication $publication): RedirectResponse
    {
        $publication->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Publication deleted.')]);

        return to_route('dashboard.publications.index');
    }
}
