<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPolicy;
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
}
