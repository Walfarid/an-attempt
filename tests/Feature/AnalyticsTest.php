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
