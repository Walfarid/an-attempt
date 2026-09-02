<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdatePrivacyPolicyRequest;
use App\Models\PrivacyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyPolicyController extends Controller
{
    /**
     * Show the privacy policy edit form.
     */
    public function edit(): Response
    {
        return Inertia::render('dashboard/PrivacyPolicy', [
            'policy' => PrivacyPolicy::current()->only(['body']),
        ]);
    }

    /**
     * Update the privacy policy.
     */
    public function update(UpdatePrivacyPolicyRequest $request): RedirectResponse
    {
        PrivacyPolicy::current()->update($request->validated());

        Cache::forget('sitemap.xml');
        Cache::forget('sitemap.last_modified');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Privacy policy updated.')]);

        return to_route('dashboard.privacy.edit');
    }
}
