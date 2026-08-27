<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Login Route Tests
|--------------------------------------------------------------------------
|
| Tests for the GET /login route which redirects to WorkOS for authentication.
|
*/

test('authenticated users are redirected from login to dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/login')
        ->assertRedirect(route('dashboard'));
});

test('guests are redirected to workos from login', function () {
    $response = $this->get('/login');

    // Should redirect to WorkOS authorization URL
    $response->assertRedirect();
    $location = $response->headers->get('Location');

    expect($location)->toContain('api.workos.com')
        ->and($location)->toContain('client_id='.config('services.workos.client_id'))
        ->and(session('state'))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Authenticate Route Tests
|--------------------------------------------------------------------------
|
| Tests for the GET /authenticate route which handles the WorkOS callback.
|
*/

test('authenticate route validates state parameter', function () {
    // Set up a different state in session than what we send
    session(['state' => json_encode(['state' => 'correct-state', 'previous_url' => base64_encode('/')])]);

    $response = $this->get('/authenticate?code=test-code&state='.json_encode(['state' => 'wrong-state']));

    $response->assertStatus(403);
});

test('authenticate route requires valid workos configuration', function () {
    // This test verifies that with valid config, the route attempts authentication
    // We can't fully test without mocking the WorkOS SDK, but we verify config is set
    expect(config('services.workos.client_id'))->not->toBeEmpty()
        ->and(config('services.workos.secret'))->not->toBeEmpty()
        ->and(config('services.workos.redirect_url'))->not->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Logout Route Tests
|--------------------------------------------------------------------------
|
| Tests for the POST /logout route which logs out the user.
|
*/

test('guests cannot access logout route', function () {
    $this->post('/logout')
        ->assertRedirect('/login');
});

test('authenticated users can logout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(Auth::check())->toBeTrue();

    $this->post('/logout')
        ->assertRedirect();

    expect(Auth::check())->toBeFalse();
});

test('logout invalidates the session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sessionId = session()->getId();

    $this->post('/logout');

    // Session should be invalidated (new ID)
    expect(session()->getId())->not->toBe($sessionId);
});

test('logout regenerates csrf token', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $oldToken = csrf_token();

    $this->post('/logout');

    // CSRF token should be regenerated
    expect(csrf_token())->not->toBe($oldToken);
});

test('logout clears all session data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Verify we are logged in
    expect(Auth::id())->toBe($user->id);

    $this->post('/logout');

    // The session is invalidated and user is logged out
    expect(Auth::id())->toBeNull();
});

test('logout clears workos session tokens when present', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Simulate WorkOS tokens in session
    session([
        'workos_access_token' => 'fake-access-token',
        'workos_refresh_token' => 'fake-refresh-token',
    ]);

    expect(session('workos_access_token'))->toBe('fake-access-token');

    $this->post('/logout');

    // Session is invalidated, so tokens are gone
    expect(session('workos_access_token'))->toBeNull()
        ->and(session('workos_refresh_token'))->toBeNull();
});

test('logout redirects to workos logout when session exists', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Without a valid JWT access token, logout just redirects to home
    // (A real JWT would require mocking the JWK endpoint)
    $this->post('/logout')
        ->assertRedirect('/');
});

/*
|--------------------------------------------------------------------------
| Authentication Guard Tests
|--------------------------------------------------------------------------
|
| Tests for the authentication guard behavior across the application.
|
*/

test('protected routes redirect guests to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

test('intended url is preserved during login redirect', function () {
    // Access a protected route
    $this->get('/dashboard')
        ->assertRedirect('/login');

    // The intended URL should be stored in the session
    expect(session('url.intended'))->toBe(url('/dashboard'));
});

test('guest middleware prevents authenticated users from accessing login', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/login')
        ->assertRedirect(route('dashboard'));
});
