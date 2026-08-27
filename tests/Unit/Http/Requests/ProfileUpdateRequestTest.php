<?php

use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('rules returns the expected validation structure', function () {
    $request = new ProfileUpdateRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['name'])
        ->and($rules['name'])->toContain('required', 'string', 'max:255');
});

test('request uses default authorization (no authorize method defined)', function () {
    $request = new ProfileUpdateRequest;

    // In Laravel, FormRequest doesn't define authorize() by default.
    // Authorization is handled via route middleware (auth, ValidateSessionWithWorkOS).
    expect(method_exists($request, 'authorize'))->toBeFalse();
});

// Validation tests via HTTP request
test('name is required', function () {
    $this->actingAs(User::factory()->create());

    $this->patch('/settings/profile', [])
        ->assertInvalid('name');
});

test('name must be a string', function () {
    $this->actingAs(User::factory()->create());

    $this->patch('/settings/profile', [
        'name' => ['not', 'a', 'string'],
    ])->assertInvalid('name');
});

test('numeric name fails validation', function () {
    $this->actingAs(User::factory()->create());

    $this->patch('/settings/profile', [
        'name' => 12345,
    ])->assertInvalid('name');
});

test('name cannot exceed 255 characters', function () {
    $this->actingAs(User::factory()->create());

    $this->patch('/settings/profile', [
        'name' => str_repeat('a', 256),
    ])->assertInvalid('name');
});

test('name with exactly 255 characters passes validation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->patch('/settings/profile', [
        'name' => str_repeat('a', 255),
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe(str_repeat('a', 255));
});

test('valid name updates user profile', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    $this->actingAs($user);

    $this->patch('/settings/profile', [
        'name' => 'John Doe',
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe('John Doe');
});

test('name with unicode characters passes validation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->patch('/settings/profile', [
        'name' => 'José García 你好',
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe('José García 你好');
});

test('name with spaces and special characters passes validation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->patch('/settings/profile', [
        'name' => "Mary-Jane O'Connor",
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe("Mary-Jane O'Connor");
});

test('name with punctuation passes validation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->patch('/settings/profile', [
        'name' => 'Dr. Smith (Jr.)',
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe('Dr. Smith (Jr.)');
});

test('single character name passes validation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->patch('/settings/profile', [
        'name' => 'A',
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe('A');
});

test('empty string name fails validation', function () {
    $this->actingAs(User::factory()->create());

    $this->patch('/settings/profile', [
        'name' => '',
    ])->assertInvalid('name');
});

test('null name fails validation', function () {
    $this->actingAs(User::factory()->create());

    $this->patch('/settings/profile', [
        'name' => null,
    ])->assertInvalid('name');
});

test('guest cannot update profile', function () {
    $this->patch('/settings/profile', [
        'name' => 'John Doe',
    ])->assertRedirect('/login');
});
