<?php

/**
 * Performance baseline benchmark.
 *
 * Measures response time (ms) and DB query count for key routes,
 * plus the frontend bundle size. Run with: php benchmarks/baseline.php
 *
 * Public routes go through the full HTTP stack. Dashboard routes call
 * controller methods directly (they require auth middleware).
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
 * Benchmark a public route through the full HTTP stack.
 *
 * @param  list<array{0: string, 1: string}>  $routes
 * @return array<string, array{method: string, avg_ms: float, min_ms: float, max_ms: float, avg_queries: float, queries: list<int>}>
 */
function benchmarkRoutes(array $routes): array
{
    global $app;
    $results = [];

    foreach ($routes as [$method, $uri]) {
        $times = [];
        $queryCounts = [];

        for ($i = 0; $i < 5; $i++) {
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
 * @return array<string, array{method: string, avg_ms: float, min_ms: float, max_ms: float, avg_queries: float, queries: list<int>}>
 */
function benchmarkControllers(array $endpoints): array
{
    $results = [];

    foreach ($endpoints as $endpoint) {
        $label = $endpoint[0];
        $callable = $endpoint[1];
        $args = $endpoint[2] ?? [];
        $times = [];
        $queryCounts = [];

        for ($i = 0; $i < 5; $i++) {
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

/**
 * @param  list<float>  $times
 * @param  list<int>  $queryCounts
 * @return array{method: string, avg_ms: float, min_ms: float, max_ms: float, avg_queries: float, queries: list<int>}
 */
function formatResult(string $method, array $times, array $queryCounts): array
{
    $avgTime = round(array_sum($times) / count($times), 2);
    $avgQueries = round(array_sum($queryCounts) / count($queryCounts), 1);

    return [
        'method' => $method,
        'avg_ms' => $avgTime,
        'min_ms' => round(min($times), 2),
        'max_ms' => round(max($times), 2),
        'avg_queries' => $avgQueries,
        'queries' => $queryCounts,
    ];
}

function printResults(array $results, string $section): void
{
    echo "\n--- {$section} ---\n";
    foreach ($results as $uri => $data) {
        echo sprintf(
            "%-55s %8.2f ms (min: %8.2f, max: %8.2f) | %3.1f queries\n",
            $uri,
            $data['avg_ms'],
            $data['min_ms'],
            $data['max_ms'],
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

// --- Bundle size ---
$buildDir = __DIR__.'/../public/build';
$bundleSize = 0;
$bundleFiles = [];

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
            }
        }
    }
}

echo "\n--- Bundle ---\n";
foreach ($bundleFiles as $file => $size) {
    echo sprintf("  %-50s %s KB\n", $file, number_format($size / 1024, 1));
}
echo sprintf("  Total: %s KB\n\n", number_format($bundleSize / 1024, 1));

// --- Save ---
$output = [
    'timestamp' => now()->toIso8601String(),
    'routes' => $publicResults,
    'dashboard' => $dashboardResults,
    'bundle' => [
        'total_kb' => round($bundleSize / 1024, 1),
        'files' => $bundleFiles,
    ],
];

file_put_contents(
    __DIR__.'/baseline.json',
    json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "Baseline saved to benchmarks/baseline.json\n";
