<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldTrack($request, $response)) {
            return;
        }

        try {
            PageView::create([
                'path' => $request->path(),
                'title' => null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
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
