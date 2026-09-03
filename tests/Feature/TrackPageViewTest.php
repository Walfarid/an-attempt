<?php

use App\Jobs\RecordPageView;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Profile;
use Illuminate\Support\Facades\Queue;

test('page views are recorded for public pages', function () {
    Profile::factory()->create();

    $this->get('/')->assertOk();

    expect(PageView::where('path', '/')->count())->toBe(1);
});

test('machine-facing resources are not recorded as page views', function () {
    Profile::factory()->create();
    Post::factory()->create(['published_at' => now()->subDay()]);

    $this->get('/sitemap.xml')->assertOk();

    expect(PageView::where('path', 'sitemap.xml')->count())->toBe(0);
});

test('inertia partial reloads are not recorded as page views', function () {
    Profile::factory()->create();

    $this->withHeaders(inertiaHeaders([
        'X-Inertia-Partial-Component' => 'Welcome',
        'X-Inertia-Partial-Data' => 'skills',
    ]))->get('/')->assertOk();

    expect(PageView::where('path', '/')->count())->toBe(0);
});

test('inertia prefetches are not recorded as page views', function () {
    Profile::factory()->create();
    Post::factory()->create(['published_at' => now()->subDay()]);

    $this->withHeaders(inertiaHeaders(['Purpose' => 'prefetch']))
        ->get('/posts')
        ->assertOk();

    expect(PageView::where('path', 'posts')->count())->toBe(0);
});

test('inertia spa navigations are still recorded as page views', function () {
    Profile::factory()->create();
    Post::factory()->create(['published_at' => now()->subDay()]);

    $this->withHeaders(inertiaHeaders())->get('/posts')->assertOk();

    expect(PageView::where('path', 'posts')->count())->toBe(1);
});

test('page view tracking dispatches a RecordPageView job', function () {
    Queue::fake();
    Profile::factory()->create();

    $this->get('/')->assertOk();

    Queue::assertPushed(RecordPageView::class, fn (RecordPageView $job) => $job->data['path'] === '/');
});

test('page view timestamp is captured at dispatch time, not worker handle time', function () {
    Profile::factory()->create();
    $dispatchedAt = now();

    $this->travelTo($dispatchedAt, fn () => $this->get('/')->assertOk());

    // Simulate queue lag: the worker processes the job a day later.
    $this->travel(1)->days();

    expect(PageView::first()->viewed_at->timestamp)->toBe($dispatchedAt->timestamp);
});
