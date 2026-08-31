<?php

/**
 * Performance baseline benchmark.
 *
 * Measures response time (ms) and DB query count for key routes,
 * plus the frontend bundle size. Run with: php benchmarks/baseline.php
 *
 * Public routes go through the full HTTP stack. Dashboard routes call
 * controller methods directly (they require auth middleware).
 *
 * Features:
 * - Warmup request per route/endpoint (eliminates cold-cache outliers)
 * - Median as headline (robust against outliers), plus mean, min, p95
 * - Adaptive iterations: 15 for sub-5ms endpoints, 7 otherwise
 * - Gzipped bundle size (wire weight)
 */

require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\EducationController;
use App\Http\Controllers\Dashboard\ExperienceController;
use App\Http\Controllers\Dashboard\PostController;
use App\Http\Controllers\Dashboard\PrivacyPolicyController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\ProjectController;
use App\Http\Controllers\Dashboard\PublicationController;
use App\Http\Controllers\Dashboard\SkillController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/**
 * Calculate percentile of a sorted array.
 *
 * @param  list<float>  $sorted
 */
function percentile(array $sorted, float $p): float
{
    $count = count($sorted);
    if ($count === 1) {
        return $sorted[0];
    }

    $index = ($p / 100) * ($count - 1);
    $lower = (int) floor($index);
    $upper = (int) ceil($index);
    $fraction = $index - $lower;

    if ($lower === $upper) {
        return $sorted[$lower];
    }

    return $sorted[$lower] + $fraction * ($sorted[$upper] - $sorted[$lower]);
}

/**
 * @param  list<float>  $times
 * @param  list<int>  $queryCounts
 * @return array{method: string, median_ms: float, mean_ms: float, min_ms: float, p95_ms: float, avg_queries: float, queries: list<int>}
 */
function formatResult(string $method, array $times, array $queryCounts): array
{
    $sorted = $times;
    sort($sorted);

    $median = percentile($sorted, 50);
    $mean = array_sum($times) / count($times);
    $min = min($times);
    $p95 = percentile($sorted, 95);
    $avgQueries = array_sum($queryCounts) / count($queryCounts);

    return [
        'method' => $method,
        'median_ms' => round($median, 2),
        'mean_ms' => round($mean, 2),
        'min_ms' => round($min, 2),
        'p95_ms' => round($p95, 2),
        'avg_queries' => round($avgQueries, 1),
        'queries' => $queryCounts,
    ];
}

/**
 * Benchmark a public route through the full HTTP stack.
 *
 * @param  list<array{0: string, 1: string}>  $routes
 * @return array<string, array{method: string, median_ms: float, mean_ms: float, min_ms: float, p95_ms: float, avg_queries: float, queries: list<int>}>
 */
function benchmarkRoutes(array $routes): array
{
    global $app;
    $results = [];

    foreach ($routes as [$method, $uri]) {
        // Warmup: run once to warm caches, discard result
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request = Request::create($uri, $method);
        try {
            $response = $app->handle($request);
            $app->terminate($request, $response);
        } catch (Throwable) {
            // Warmup error is okay, we're just warming caches
        }
        DB::disableQueryLog();

        // Now run the timed iterations
        $times = [];
        $queryCounts = [];

        // Use 7 iterations for public routes (typically > 5ms)
        $iterations = 7;

        for ($i = 0; $i < $iterations; $i++) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $request = Request::create($uri, $method);

            $start = microtime(true);
            try {
                $response = $app->handle($request);
                $app->terminate($request, $response);
            } catch (Throwable) {
                // Measure time even on error.
            }
            $elapsed = (microtime(true) - $start) * 1000;

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $times[] = $elapsed;
            $queryCounts[] = count($queries);
        }

        $results[$uri] = formatResult($method, $times, $queryCounts);
    }

    return $results;
}

/**
 * Benchmark a dashboard controller method directly (bypasses auth middleware).
 *
 * @param  list<array{0: string, 1: callable, 2?: array<mixed>}>  $endpoints
 * @return array<string, array{method: string, median_ms: float, mean_ms: float, min_ms: float, p95_ms: float, avg_queries: float, queries: list<int>}>
 */
function benchmarkControllers(array $endpoints): array
{
    $results = [];

    foreach ($endpoints as $endpoint) {
        $label = $endpoint[0];
        $callable = $endpoint[1];
        $args = $endpoint[2] ?? [];

        // Warmup: run once to warm caches, discard result
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $callable(...$args);
        } catch (Throwable) {
            // Warmup error is okay
        }
        DB::disableQueryLog();

        // Quick measurement to determine iteration count
        DB::flushQueryLog();
        DB::enableQueryLog();
        $start = microtime(true);
        try {
            $callable(...$args);
        } catch (Throwable) {
        }
        $sampleTime = (microtime(true) - $start) * 1000;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Adaptive iterations: 15 for sub-5ms endpoints, 7 otherwise
        $iterations = ($sampleTime < 5.0) ? 15 : 7;

        $times = [];
        $queryCounts = [];

        for ($i = 0; $i < $iterations; $i++) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $start = microtime(true);
            try {
                $callable(...$args);
            } catch (Throwable) {
                // Measure time even on error.
            }
            $elapsed = (microtime(true) - $start) * 1000;

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $times[] = $elapsed;
            $queryCounts[] = count($queries);
        }

        $results[$label] = formatResult('GET', $times, $queryCounts);
    }

    return $results;
}

function printResults(array $results, string $section): void
{
    echo "\n--- {$section} ---\n";
    foreach ($results as $uri => $data) {
        echo sprintf(
            "%-55s median: %6.2f ms (mean: %6.2f, min: %6.2f, p95: %6.2f) | %3.1f queries\n",
            $uri,
            $data['median_ms'],
            $data['mean_ms'],
            $data['min_ms'],
            $data['p95_ms'],
            $data['avg_queries'],
        );
    }
}

// --- Public routes (full HTTP stack) ---
$publicRoutes = [
    ['GET', '/'],
    ['GET', '/posts'],
    ['GET', '/posts/what-clean-architecture-means-in-practice'],
    ['GET', '/sitemap.xml'],
];

$publicResults = benchmarkRoutes($publicRoutes);
printResults($publicResults, 'Public routes');

// --- Dashboard endpoints (direct controller calls, bypasses auth) ---
$dashboardEndpoints = [
    ['dashboard (analytics)', fn () => app(AnalyticsController::class)->index(Request::create('/dashboard'))],
    ['dashboard/posts', fn () => app(PostController::class)->index()],
    ['dashboard/projects', fn () => app(ProjectController::class)->index()],
    ['dashboard/skills', fn () => app(SkillController::class)->index()],
    ['dashboard/experience', fn () => app(ExperienceController::class)->index()],
    ['dashboard/educations', fn () => app(EducationController::class)->index()],
    ['dashboard/publications', fn () => app(PublicationController::class)->index()],
    ['dashboard/profile/edit', fn () => app(ProfileController::class)->edit()],
    ['dashboard/privacy/edit', fn () => app(PrivacyPolicyController::class)->edit()],
];

$dashboardResults = benchmarkControllers($dashboardEndpoints);
printResults($dashboardResults, 'Dashboard endpoints');

// --- Bundle size (raw and gzipped) ---
$buildDir = __DIR__.'/../public/build';
$bundleSize = 0;
$bundleSizeGzipped = 0;
$bundleFiles = [];
$bundleFilesGzipped = [];

if (is_dir($buildDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($buildDir)
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = $file->getExtension();
            if (in_array($ext, ['js', 'css'])) {
                $relative = str_replace($buildDir.'/', '', $file->getPathname());
                $size = $file->getSize();
                $bundleSize += $size;
                $bundleFiles[$relative] = $size;

                // Gzipped size (wire weight)
                $content = file_get_contents($file->getPathname());
                $gzipped = strlen(gzencode($content, 9));
                $bundleSizeGzipped += $gzipped;
                $bundleFilesGzipped[$relative] = $gzipped;
            }
        }
    }
}

echo "\n--- Bundle (raw / gzipped) ---\n";
foreach ($bundleFiles as $file => $size) {
    $gzipped = $bundleFilesGzipped[$file] ?? 0;
    echo sprintf(
        "  %-50s %7.1f KB / %5.1f KB\n",
        $file,
        $size / 1024,
        $gzipped / 1024
    );
}
echo sprintf(
    "  Total: %7.1f KB / %5.1f KB (gzipped)\n\n",
    $bundleSize / 1024,
    $bundleSizeGzipped / 1024
);

// --- Save ---
$output = [
    'timestamp' => now()->toIso8601String(),
    'routes' => $publicResults,
    'dashboard' => $dashboardResults,
    'bundle' => [
        'total_kb' => round($bundleSize / 1024, 1),
        'total_gzipped_kb' => round($bundleSizeGzipped / 1024, 1),
        'files' => $bundleFiles,
        'files_gzipped' => $bundleFilesGzipped,
    ],
];

file_put_contents(
    __DIR__.'/baseline.json',
    json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "Baseline saved to benchmarks/baseline.json\n";
