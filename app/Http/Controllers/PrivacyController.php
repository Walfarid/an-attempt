<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPolicy;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    /**
     * The public privacy disclosure page.
     *
     * Sets Last-Modified via a request attribute so the CachePublicResponses
     * middleware can promote it to a response header after Inertia converts
     * the response.
     */
    public function show(Request $request): Response
    {
        $policy = PrivacyPolicy::current();

        // Conditional request: if the browser has a fresh copy, skip the
        // Inertia serialization entirely — mirrors BlogController::show and
        // GuideController::show.
        if ($this->isNotModified($request, $policy->updated_at)) {
            abort(304);
        }

        // Store the timestamp for the middleware to read after Inertia
        // converts the response to a Symfony response.
        $request->attributes->set('last_modified', $policy->updated_at);

        return Inertia::render('Privacy', [
            'policy' => tap($policy, function (PrivacyPolicy $p): void {
                $p->body_html = $p->bodyHtml();
                $p->makeHidden(['body', 'created_at', 'id']);
            }),
        ]);
    }

    /**
     * Check whether the request's If-Modified-Since header matches the
     * given timestamp (second precision, per HTTP spec).
     */
    private function isNotModified(Request $request, CarbonInterface $lastModified): bool
    {
        $since = $request->header('If-Modified-Since');

        if ($since === null) {
            return false;
        }

        return $lastModified->startOfSecond()->timestamp <= strtotime($since);
    }
}
