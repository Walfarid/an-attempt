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
        ->missing('auth.user.id')
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

test('sidebar is absent for guests on public pages', function () {
    Profile::factory()->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->missing('sidebarOpen')
    );
});

test('sidebar is open by default for authenticated users when no cookie is set', function () {
    Profile::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('sidebarOpen', true)
    );
});

test('sidebar respects the sidebar_state cookie set to true', function () {
    Profile::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->withUnencryptedCookie('sidebar_state', 'true')->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('sidebarOpen', true)
    );
});

test('sidebar is closed when the sidebar_state cookie is false', function () {
    Profile::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->withUnencryptedCookie('sidebar_state', 'false')->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('sidebarOpen', false)
    );
});

test('share method returns expected keys for guests', function () {
    $middleware = new HandleInertiaRequests;
    $request = Request::create('/');
    $request->setUserResolver(fn () => null);

    $shared = $middleware->share($request);

    expect($shared)->toHaveKeys(['name', 'auth'])
        ->and($shared)->not->toHaveKey('sidebarOpen');
});

test('share method includes sidebarOpen for authenticated users', function () {
    $middleware = new HandleInertiaRequests;
    $request = Request::create('/');
    $user = User::factory()->make();
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared)->toHaveKeys(['name', 'auth', 'sidebarOpen']);
});

test('version method returns a string or null', function () {
    $middleware = new HandleInertiaRequests;
    $request = Request::create('/');

    $version = $middleware->version($request);

    expect($version)->toBeString()->or->toBeNull();
});

test('adsense props are shared only when consent is accepted and configured', function () {
    Profile::factory()->create();
    config()->set('services.adsense.client_id', 'ca-pub-test');
    config()->set('services.adsense.slot_id', '1234567890');

    $this->withUnencryptedCookie('consent', 'accepted')->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('adsenseClientId', 'ca-pub-test')
            ->where('adsenseSlotId', '1234567890')
        );
});

test('adsense props are absent without the consent cookie', function () {
    Profile::factory()->create();
    config()->set('services.adsense.client_id', 'ca-pub-test');
    config()->set('services.adsense.slot_id', '1234567890');

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->missing('adsenseClientId')
            ->missing('adsenseSlotId')
        );
});

test('adsense props are absent when consent is declined', function () {
    Profile::factory()->create();
    config()->set('services.adsense.client_id', 'ca-pub-test');
    config()->set('services.adsense.slot_id', '1234567890');

    $this->withUnencryptedCookie('consent', 'declined')->get('/')
        ->assertInertia(fn ($page) => $page
            ->missing('adsenseClientId')
            ->missing('adsenseSlotId')
        );
});

test('adsense props are absent when consent is accepted but services are unconfigured', function () {
    Profile::factory()->create();
    config()->set('services.adsense.client_id', null);
    config()->set('services.adsense.slot_id', null);

    $this->withUnencryptedCookie('consent', 'accepted')->get('/')
        ->assertInertia(fn ($page) => $page
            ->missing('adsenseClientId')
            ->missing('adsenseSlotId')
        );
});
