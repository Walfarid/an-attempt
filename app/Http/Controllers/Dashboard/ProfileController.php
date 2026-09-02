<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateProfileRequest;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the portfolio profile edit form.
     */
    public function edit(): Response
    {
        return Inertia::render('dashboard/Profile', [
            'profile' => Profile::query()->firstOrFail([
                'name', 'headline', 'bio', 'location', 'github_url', 'linkedin_url',
            ]),
        ]);
    }

    /**
     * Update the portfolio profile.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        Profile::current()->update($request->validated());

        Cache::forget('profile.name');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('dashboard.profile.edit');
    }
}
