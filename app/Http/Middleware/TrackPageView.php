<?php

namespace App\Http\Middleware;

use App\Jobs\RecordPageView;
use App\Support\Analytics;
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
                // Raw IPs are never stored — only an HMAC digest keyed with
                // the app key (see App\Support\Analytics::anonymizeIp).
                'ip' => Analytics::anonymizeIp($request->ip()),
                // page_views.user_agent and referrer are VARCHAR(255); trim
                // unbounded strings before dispatch so the worker INSERT
                // cannot silently fail at column width.
                'user_agent' => $request->userAgent() !== null ? mb_substr($request->userAgent(), 0, 255) : null,
                'referrer' => ($referer = $request->header('referer')) !== null ? mb_substr($referer, 0, 255) : null,
                'user_id' => $request->user()?->id,
                'viewed_at' => now(),
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

        if ($request->is('favicon.ico') || $request->is('robots.txt') || $request->is('sitemap.xml') || $request->is('ads.txt')) {
            return false;
        }

        return true;
    }
}
