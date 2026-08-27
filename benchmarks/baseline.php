<?php

/**
 * Performance baseline benchmark.
 *
 * Measures response time (ms) and DB query count for key routes,
 * plus the frontend bundle size. Run with: php benchmarks/baseline.php
 */

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$routes = [
    ['GET', '/'],
    ['GET', '/posts'],
    ['GET', '/posts/what-clean-architecture-means-in-practice'],
    ['GET', '/sitemap.xml'],
];

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
            $elapsed = (microtime(true) - $start) * 1000;
            $app->terminate($request, $response);
        } catch (Throwable $e) {
            $elapsed = (microtime(true) - $start) * 1000;
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $times[] = $elapsed;
        $queryCounts[] = count($queries);
    }

    $avgTime = round(array_sum($times) / count($times), 2);
    $avgQueries = round(array_sum($queryCounts) / count($queryCounts), 1);

    $results[$uri] = [
        'method' => $method,
        'avg_ms' => $avgTime,
        'min_ms' => round(min($times), 2),
        'max_ms' => round(max($times), 2),
        'avg_queries' => $avgQueries,
        'queries' => $queryCounts,
    ];

    echo sprintf(
        "%-55s %8.2f ms (min: %8.2f, max: %8.2f) | %3.1f queries\n",
        $uri,
        $avgTime,
        min($times),
        max($times),
        $avgQueries,
    );
}

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

$output = [
    'timestamp' => now()->toIso8601String(),
    'routes' => $results,
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
