<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\PageView;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    /**
     * Dashboard overview — last 14 days of real analytics.
     */
    public function index(Request $request): \Inertia\Response
    {
        $days = 14;

        // Single 28-day query per model: derive current series, totals, and
        // previous-period totals from one result set instead of 4 separate queries.
        $viewData = $this->dailyCountsWithPrev(PageView::query(), 'viewed_at', $days);
        $clickData = $this->dailyCountsWithPrev(Click::query(), 'clicked_at', $days);

        $totalVisitors = $viewData['current_total'];
        $totalClicks = $clickData['current_total'];
        $prevVisitors = $viewData['prev_total'];
        $prevClicks = $clickData['prev_total'];

        $ctr = $totalVisitors > 0 ? ($totalClicks / $totalVisitors) * 100 : 0;
        $prevCtr = $prevVisitors > 0 ? ($prevClicks / $prevVisitors) * 100 : 0;

        // Pre-aggregate clicks in a derived table and LEFT JOIN once,
        // avoiding a correlated subquery per top-page row.
        $cutoff = now()->subDays($days);

        $topPages = PageView::select(
            'page_views.path',
            DB::raw('count(*) as visitors'),
            DB::raw('COALESCE(MAX(c.clicks), 0) as clicks'),
        )
            ->joinSub(
                Click::select('path')
                    ->selectRaw('count(*) as clicks')
                    ->where('clicked_at', '>=', $cutoff)
                    ->groupBy('path'),
                'c',
                'c.path',
                '=',
                'page_views.path',
                'left',
            )
            ->where('page_views.viewed_at', '>=', $cutoff)
            ->groupBy('page_views.path')
            ->orderByDesc('visitors')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'path' => $row->path,
                'title' => $this->pathTitle($row->path),
                'visitors' => (int) $row->visitors, // @phpstan-ignore property.notFound
                'clicks' => (int) $row->clicks, // @phpstan-ignore property.notFound
            ]);

        $kpis = [
            ['key' => 'visitors', 'label' => 'Visitors', 'value' => $totalVisitors, 'delta' => $this->delta($totalVisitors, $prevVisitors), 'format' => 'number'],
            ['key' => 'clicks', 'label' => 'Clicks', 'value' => $totalClicks, 'delta' => $this->delta($totalClicks, $prevClicks), 'format' => 'number'],
            ['key' => 'ctr', 'label' => 'Click-through rate', 'value' => round($ctr, 1), 'delta' => $this->delta($ctr, $prevCtr), 'format' => 'percent'],
        ];

        return inertia('Dashboard', [
            'kpis' => $kpis,
            'visitorSeries' => $viewData['series'],
            'clickSeries' => $clickData['series'],
            'topPages' => $topPages,
        ]);
    }

    /**
     * Accept a click event from the frontend tracker.
     */
    public function storeClick(Request $request): Response
    {
        $validated = $request->validate([
            'path' => 'required|string|max:500',
            'element' => 'nullable|string|max:200',
            'label' => 'nullable|string|max:200',
        ]);

        try {
            Click::create([
                'path' => $validated['path'],
                'element' => $validated['element'] ?? null,
                'label' => $validated['label'] ?? null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => $request->user()?->id,
                'clicked_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('Click tracking failed', ['error' => $e->getMessage()]);
        }

        return response()->noContent();
    }

    /**
     * Build daily time series for the current period and compute totals for
     * both the current and previous period from a single 2N-day query.
     *
     * @param  Builder<PageView>|Builder<Click>  $query
     * @param  literal-string  $dateColumn
     * @return array{series: list<array{label: string, value: int}>, current_total: int, prev_total: int}
     */
    private function dailyCountsWithPrev(Builder $query, string $dateColumn, int $days): array
    {
        $counts = (clone $query)
            ->selectRaw("date({$dateColumn}) as day, count(*) as total")
            ->where($dateColumn, '>=', now()->subDays($days * 2 - 1))
            ->groupBy('day')
            ->pluck('total', 'day');

        $today = now()->startOfDay();
        $currentStart = $today->copy()->subDays($days - 1);
        $prevStart = $currentStart->copy()->subDays($days);

        $currentSeries = [];
        $currentTotal = 0;
        $prevTotal = 0;

        $period = CarbonPeriod::create($currentStart, '1 day', $today);
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $value = (int) ($counts[$key] ?? 0);
            $currentSeries[] = [
                'label' => $date->format('M j'),
                'value' => $value,
            ];
            $currentTotal += $value;
        }

        $period = CarbonPeriod::create($prevStart, '1 day', $currentStart->copy()->subDay());
        foreach ($period as $date) {
            $prevTotal += (int) ($counts[$date->format('Y-m-d')] ?? 0);
        }

        return [
            'series' => $currentSeries,
            'current_total' => $currentTotal,
            'prev_total' => $prevTotal,
        ];
    }

    private function delta(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function pathTitle(string $path): string
    {
        return match ($path) {
            '/' => 'Home',
            '/posts' => 'Blog',
            default => ucfirst(trim($path, '/')),
        };
    }
}
