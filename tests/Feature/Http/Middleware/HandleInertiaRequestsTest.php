<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;

test('handle inertia requests shares the app name', function () {
    Profile::factory()->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->has('name')
        ->where('name', config('app.name'))
    );
});

test('handle inertia requests shares the authenticated user', function () {
    Profile::factory()->create();
    $user = User::factory()->create(['name' => 'Test User']);

    $response = $this->actingAs($user)->get('/');

    $response->assertInertia(fn ($page) => $page
        ->has('auth.user')
        ->where('auth.user.id', $user->id)
        ->where('auth.user.name', 'Test User')
    );
});

test('handle inertia requests shares null user for guests', function () {
    Profile::factory()->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('auth.user', null)
    );
});

test('sidebar is open by default when no cookie is set', function () {
    Profile::factory()->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('sidebarOpen', true)
    );
});

test('sidebar respects the sidebar_state cookie set to true', function () {
    Profile::factory()->create();

    $response = $this->withUnencryptedCookie('sidebar_state', 'true')->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('sidebarOpen', true)
    );
});

test('sidebar is closed when the sidebar_state cookie is false', function () {
    Profile::factory()->create();

    $response = $this->withUnencryptedCookie('sidebar_state', 'false')->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('sidebarOpen', false)
    );
});

test('share method returns expected keys', function () {
    $middleware = new HandleInertiaRequests;
    $request = Request::create('/');
    $request->setUserResolver(fn () => null);

    $shared = $middleware->share($request);

    expect($shared)->toHaveKeys(['name', 'auth', 'sidebarOpen']);
});

test('version method returns a string or null', function () {
    $middleware = new HandleInertiaRequests;
    $request = Request::create('/');

    $version = $middleware->version($request);

    expect($version)->toBeString()->or->toBeNull();
});
