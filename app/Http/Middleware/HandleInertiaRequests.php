<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $shared = [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->only(['name', 'email', 'avatar']),
            ],
        ];

        // AdSense slot ID — only shared when consent is accepted, so the
        // client-side ad component can render without a second round-trip.
        if ($request->cookie('consent') === 'accepted') {
            $adClientId = config('services.adsense.client_id');
            $adSlotId = config('services.adsense.slot_id');

            if ($adClientId) {
                $shared['adsenseClientId'] = $adClientId;
            }
            if ($adSlotId) {
                $shared['adsenseSlotId'] = $adSlotId;
            }
        }

        // Dashboard-only: the sidebar lives inside the authenticated
        // AppShell layout. Public pages never render it, so keeping the
        // key off the wire for unauthenticated visits saves ~17 B per
        // response.
        if ($request->user() !== null) {
            $shared['sidebarOpen'] = ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true';
        }

        return $shared;
    }
}
