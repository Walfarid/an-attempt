<?php

use App\Jobs\RecordClick;
use App\Models\Click;
use App\Models\PageView;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

test('authenticated users can see the dashboard with analytics data', function () {
    $user = User::factory()->create();

    PageView::create([
        'path' => '/',
        'ip' => '127.0.0.1',
        'viewed_at' => now(),
    ]);

    Click::create([
        'path' => '/',
        'element' => 'hero-cta',
        'ip' => '127.0.0.1',
        'clicked_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('kpis')
            ->has('visitorSeries')
            ->has('clickSeries')
            ->has('topPages'),
        );
});

test('click tracking endpoint stores click events', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/analytics/clicks', [
            'path' => '/posts',
            'element' => 'post-link',
            'label' => 'My First Post',
        ])
        ->assertNoContent();

    $this->assertDatabaseHas('clicks', [
        'path' => '/posts',
        'element' => 'post-link',
        'label' => 'My First Post',
    ]);
});

test('click tracking dispatches a RecordClick job', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/analytics/clicks', [
            'path' => '/posts',
            'element' => 'post-link',
            'label' => 'My First Post',
        ])
        ->assertNoContent();

    Queue::assertPushed(RecordClick::class, fn (RecordClick $job) => $job->data['path'] === '/posts'
        && $job->data['element'] === 'post-link'
        && $job->data['label'] === 'My First Post');
});

test('click timestamp is captured at dispatch time, not worker handle time', function () {
    $user = User::factory()->create();
    $dispatchedAt = now();

    $this->travelTo($dispatchedAt, function () use ($user): void {
        $this->actingAs($user)
            ->post('/analytics/clicks', [
                'path' => '/posts',
                'element' => 'post-link',
                'label' => 'My First Post',
            ])
            ->assertNoContent();
    });

    // Simulate queue lag: the worker processes the job a day later.
    $this->travel(1)->days();

    expect(Click::first()->clicked_at->timestamp)->toBe($dispatchedAt->timestamp);
});

test('click tracking requires path', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/analytics/clicks', [
            'element' => 'post-link',
        ])
        ->assertInvalid('path');
});

test('guests can post clicks anonymously and the click is recorded', function () {
    Route::getRoutes()->refreshNameLookups();
    $route = Route::getRoutes()->getByName('analytics.clicks.store');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:60,1')
        ->and($route->gatherMiddleware())->not->toContain('auth', ValidateSessionWithWorkOS::class);

    $this->post('/analytics/clicks', [
        'path' => '/posts',
        'element' => 'post-link',
        'label' => 'My First Post',
    ])->assertNoContent();

    $this->assertDatabaseHas('clicks', [
        'path' => '/posts',
        'element' => 'post-link',
        'label' => 'My First Post',
        'user_id' => null,
    ]);
});

test('topPages uses LEFT JOIN so pages without clicks still appear with zero clicks', function () {
    $user = User::factory()->create();

    // Path "/" — 3 views, 2 clicks
    PageView::create(['path' => '/', 'ip' => '10.0.0.1', 'viewed_at' => now()]);
    PageView::create(['path' => '/', 'ip' => '10.0.0.2', 'viewed_at' => now()]);
    PageView::create(['path' => '/', 'ip' => '10.0.0.3', 'viewed_at' => now()]);
    Click::create(['path' => '/', 'element' => 'cta', 'ip' => '10.0.0.1', 'clicked_at' => now()]);
    Click::create(['path' => '/', 'element' => 'link', 'ip' => '10.0.0.2', 'clicked_at' => now()]);

    // Path "/posts" — 2 views, 0 clicks (proves LEFT JOIN, not INNER JOIN)
    PageView::create(['path' => '/posts', 'ip' => '10.0.0.1', 'viewed_at' => now()]);
    PageView::create(['path' => '/posts', 'ip' => '10.0.0.2', 'viewed_at' => now()]);

    // Path "/about" — 1 view, 0 clicks
    PageView::create(['path' => '/about', 'ip' => '10.0.0.1', 'viewed_at' => now()]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(function ($page) {
            $topPages = collect($page->toArray()['props']['topPages']);

            expect($topPages)->toHaveCount(3);

            // Ordered by visitors desc: / (3), /posts (2), /about (1)
            expect($topPages[0])->toBe(['path' => '/', 'title' => 'Home', 'visitors' => 3, 'clicks' => 2]);
            expect($topPages[1])->toBe(['path' => '/posts', 'title' => 'Blog', 'visitors' => 2, 'clicks' => 0]);
            expect($topPages[2])->toBe(['path' => '/about', 'title' => 'About', 'visitors' => 1, 'clicks' => 0]);
        });
});

test('page view tracking middleware records visits on public pages', function () {
    Profile::factory()->create();

    $this->get('/')
        ->assertOk();

    $this->assertDatabaseHas('page_views', [
        'path' => '/',
    ]);
});

test('page view tracking ignores dashboard pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();

    $this->assertDatabaseMissing('page_views', [
        'path' => 'dashboard',
    ]);
});

test('click tracking rejects path over 500 characters', function () {
    $user = User::factory()->create();

    $this->withoutMiddleware(ThrottleRequests::class);

    $this->actingAs($user)
        ->post('/analytics/clicks', [
            'path' => str_repeat('a', 501),
        ])
        ->assertInvalid('path');
});

test('click tracking rejects element over 200 characters', function () {
    $user = User::factory()->create();

    $this->withoutMiddleware(ThrottleRequests::class);

    $this->actingAs($user)
        ->post('/analytics/clicks', [
            'path' => '/test',
            'element' => str_repeat('b', 201),
        ])
        ->assertInvalid('element');
});

test('click tracking rejects label over 200 characters', function () {
    $user = User::factory()->create();

    $this->withoutMiddleware(ThrottleRequests::class);

    $this->actingAs($user)
        ->post('/analytics/clicks', [
            'path' => '/test',
            'label' => str_repeat('c', 201),
        ])
        ->assertInvalid('label');
});

test('click tracking accepts path at exactly 500 characters', function () {
    $user = User::factory()->create();

    $this->withoutMiddleware(ThrottleRequests::class);

    $this->actingAs($user)
        ->post('/analytics/clicks', [
            'path' => str_repeat('a', 500),
        ])
        ->assertNoContent();
});

test('click tracking accepts element and label at exactly 200 characters', function () {
    $user = User::factory()->create();

    $this->withoutMiddleware(ThrottleRequests::class);

    $this->actingAs($user)
        ->post('/analytics/clicks', [
            'path' => '/test',
            'element' => str_repeat('b', 200),
            'label' => str_repeat('c', 200),
        ])
        ->assertNoContent();
});

test('click tracking rate limits after 60 requests per minute', function () {
    $user = User::factory()->create();

    Cache::flush();

    for ($i = 0; $i < 60; $i++) {
        $this->actingAs($user)
            ->post('/analytics/clicks', ['path' => '/test'])
            ->assertNoContent();
    }

    $this->actingAs($user)
        ->post('/analytics/clicks', ['path' => '/test'])
        ->assertStatus(429);
});
