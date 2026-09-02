<?php

use App\Models\Click;
use App\Models\PageView;
use App\Models\Profile;
use App\Models\User;

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

test('click tracking requires path', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/analytics/clicks', [
            'element' => 'post-link',
        ])
        ->assertInvalid('path');
});

test('guests cannot access click tracking', function () {
    $this->post('/analytics/clicks', [
        'path' => '/',
    ])->assertRedirect('/login');
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
