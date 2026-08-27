<?php

use App\Models\User;

test('guests are redirected from the settings profile page', function () {
    $this->get(route('profile.edit'))->assertRedirect('/login');
});

test('guests are redirected from the settings appearance page', function () {
    $this->get(route('appearance.edit'))->assertRedirect('/login');
});

test('guests cannot update profile information', function () {
    $this->patch(route('profile.update'), ['name' => 'New Name'])
        ->assertRedirect('/login');
});

test('guests cannot delete their account', function () {
    $this->delete(route('profile.destroy'))->assertRedirect('/login');
});

test('settings root redirects to profile page', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/settings')->assertRedirect('/settings/profile');
});

test('guests are redirected from settings root', function () {
    $this->get('/settings')->assertRedirect('/login');
});

test('authenticated users can view the profile settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->where('status', null));
});

test('authenticated users can view the appearance settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('appearance.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Appearance'));
});

test('profile update validates name is required', function () {
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => ''])
        ->assertInvalid('name');

    expect($user->fresh()->name)->toBe('Original Name');
});

test('profile update validates name is a string', function () {
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => ['not', 'a', 'string']])
        ->assertInvalid('name');

    expect($user->fresh()->name)->toBe('Original Name');
});

test('profile update validates name max length', function () {
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => str_repeat('a', 256)])
        ->assertInvalid('name');

    expect($user->fresh()->name)->toBe('Original Name');
});

test('profile update accepts valid name at max length', function () {
    $user = User::factory()->create(['name' => 'Original Name']);
    $maxName = str_repeat('a', 255);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $maxName])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe($maxName);
});

test('profile update flashes success toast', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Updated Name'])
        ->assertRedirect(route('profile.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Profile updated.']);
});

test('profile page displays status message when present', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['status' => 'profile-updated'])
        ->get(route('profile.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->where('status', 'profile-updated'));
});

test('profile update requires authentication via middleware', function () {
    $this->patch(route('profile.update'), ['name' => 'Hacker'])
        ->assertRedirect('/login');
});

test('profile destruction requires authentication via middleware', function () {
    $this->delete(route('profile.destroy'))->assertRedirect('/login');
});

test('appearance page uses inertia route', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('appearance.edit'));

    // The route uses Route::inertia which directly renders a component
    $response->assertOk();
});
