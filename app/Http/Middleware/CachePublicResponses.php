<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add HTTP caching headers to public, auth-independent responses.
 *
 * Only caches for unauthenticated visitors so the shared Inertia
 * `auth.user` prop is never stale for a logged-in user. The public
 * pages render identically regardless of auth state — the only
 * auth-dependent shared data is the header sign-in/dashboard link,
 * which the cache deliberately excludes.
 */
class CachePublicResponses
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() === null) {
            // Max-age 60 with SWR 300: a missed cache is revalidated in
            // the background while stale content is served, but never
            // reused stale when revalidation is possible — a consent
            // state change always produces the matching analytics markup.
            // Longer half-hour TTLs (5b3901b4) let service workers serve
            // the analytics variant to visitors who had declined.
            $response->headers->set(
                'Cache-Control',
                'public, max-age=60, stale-while-revalidate=300, must-revalidate',
            );

            // Controllers may set a `last_modified` request attribute (a
            // Carbon instance) when the content has a known modification
            // time — promote it to a Last-Modified response header so
            // browsers and CDNs can serve 304 responses.
            $lastModified = $request->attributes->get('last_modified');
            if ($lastModified !== null) {
                $response->headers->set('Last-Modified', $lastModified->toRfc7231String());
            }
        }

        return $response;
    }
}
