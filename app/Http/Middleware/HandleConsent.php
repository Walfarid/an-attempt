<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleConsent
{
    /**
     * Handle an incoming request.
     *
     * Reads the 'consent' cookie (accepted | declined) to determine whether
     * analytics and AdSense scripts may load. The cookie is set by the
     * client-side consent banner after the user makes a choice.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('consent', $request->cookies->get('consent') ?? 'unset');

        return $next($request);
    }
}
