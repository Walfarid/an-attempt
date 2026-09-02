<?php

use App\Models\Click;
use App\Models\PageView;
use Illuminate\Console\Scheduling\Schedule;

test('model prune deletes old analytics rows but keeps recent ones', function () {
    PageView::create(['path' => '/old', 'ip' => '10.0.0.1', 'viewed_at' => now()->subDays(100)]);
    PageView::create(['path' => '/recent', 'ip' => '10.0.0.2', 'viewed_at' => now()->subDays(10)]);

    Click::create(['path' => '/old', 'element' => 'a', 'ip' => '10.0.0.1', 'clicked_at' => now()->subDays(100)]);
    Click::create(['path' => '/recent', 'element' => 'b', 'ip' => '10.0.0.2', 'clicked_at' => now()->subDays(10)]);

    $this->artisan('model:prune', ['--model' => [PageView::class, Click::class]])
        ->assertSuccessful();

    expect(PageView::query()->count())->toBe(1)
        ->and(PageView::query()->where('path', '/old')->count())->toBe(0)
        ->and(PageView::query()->where('path', '/recent')->count())->toBe(1)
        ->and(Click::query()->count())->toBe(1)
        ->and(Click::query()->where('path', '/old')->count())->toBe(0)
        ->and(Click::query()->where('path', '/recent')->count())->toBe(1);
});

test('schedule includes daily model prune command', function () {
    $schedule = app(Schedule::class);

    $pruneEvent = collect($schedule->events())->first(
        fn ($event) => str_contains($event->command ?? '', 'model:prune'),
    );

    expect($pruneEvent)->not->toBeNull()
        ->and($pruneEvent->expression)->toBe('0 0 * * *');
});
