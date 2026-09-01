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
    /**
     * @param  positive-int  $maxAge  Seconds the response is fresh.
     * @param  positive-int  $staleWhileRevalidate  Seconds stale content may be served while revalidating.
     */
    public function __construct(
        private readonly int $maxAge = 60,
        private readonly int $staleWhileRevalidate = 300,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() === null) {
            $response->headers->set(
                'Cache-Control',
                "public, max-age={$this->maxAge}, stale-while-revalidate={$this->staleWhileRevalidate}",
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
