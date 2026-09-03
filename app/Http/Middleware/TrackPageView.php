<?php

namespace App\Http\Middleware;

use App\Jobs\RecordPageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Track the page view after the response has been sent.
     *
     * The write is dispatched to the queue so the Octane/FrankenPHP worker
     * is never blocked on a synchronous INSERT inside terminate().
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldTrack($request, $response)) {
            return;
        }

        try {
            RecordPageView::dispatch([
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'user_id' => $request->user()?->id,
            ]);
        } catch (\Throwable $e) {
            Log::debug('Page view tracking failed', ['error' => $e->getMessage()]);
        }
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $response->isOk()) {
            return false;
        }

        // Inertia background data requests never render a page: partial
        // reloads (deferred props, only=/except=) and prefetches fire without
        // a navigation, so counting them would double-count views.
        if ($request->hasHeader(Header::PARTIAL_COMPONENT) || $request->prefetch()) {
            return false;
        }

        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            return false;
        }

        if ($request->is('dashboard*') || $request->is('inertia*') || $request->is('_debugbar*')) {
            return false;
        }

        if ($request->is('favicon.ico') || $request->is('robots.txt') || $request->is('sitemap.xml')) {
            return false;
        }

        return true;
    }
}
