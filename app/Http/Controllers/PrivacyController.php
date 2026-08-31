<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPolicy;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    /**
     * The public privacy disclosure page.
     */
    public function show(): Response
    {
        $policy = PrivacyPolicy::current();

        return Inertia::render('Privacy', [
            'policy' => tap($policy, function (PrivacyPolicy $p): void {
                $p->body_html = $p->bodyHtml();
                $p->makeHidden(['body', 'created_at', 'id']);
            }),
        ]);
    }
}
