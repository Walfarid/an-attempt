<?php

use App\Http\Requests\Dashboard\UpdateProfileRequest;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('rules returns the expected validation structure', function () {
    $request = new UpdateProfileRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['name', 'headline', 'bio', 'location', 'github_url', 'linkedin_url'])
        ->and($rules['name'])->toContain('required', 'string')
        ->and($rules['headline'])->toContain('required', 'string')
        ->and($rules['bio'])->toContain('required', 'string')
        ->and($rules['location'])->toContain('nullable', 'string')
        ->and($rules['github_url'])->toContain('nullable', 'string', 'url')
        ->and($rules['linkedin_url'])->toContain('nullable', 'string', 'url');
});

test('name is required', function () {
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
    ])->assertInvalid('name');
});

test('headline is required', function () {
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'bio' => 'A passionate developer.',
    ])->assertInvalid('headline');
});

test('bio is required', function () {
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
    ])->assertInvalid('bio');
});

test('name must be a string', function () {
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => ['not', 'a', 'string'],
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
    ])->assertInvalid('name');
});

test('headline must be a string', function () {
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 12345,
        'bio' => 'A passionate developer.',
    ])->assertInvalid('headline');
});

test('bio must be a string', function () {
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => ['not', 'a', 'string'],
    ])->assertInvalid('bio');
});

test('location can be omitted', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
    ])->assertRedirect(route('dashboard.profile.edit'));
});

test('location must be a string when provided', function () {
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'location' => ['not', 'a', 'string'],
    ])->assertInvalid('location');
});

test('location has a maximum length of 255 characters', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'location' => str_repeat('a', 256),
    ])->assertInvalid('location');

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'location' => str_repeat('a', 255),
    ])->assertRedirect(route('dashboard.profile.edit'));
});

test('name has a maximum length of 255 characters', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => str_repeat('a', 256),
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
    ])->assertInvalid('name');

    $this->put('/dashboard/profile', [
        'name' => str_repeat('a', 255),
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
    ])->assertRedirect(route('dashboard.profile.edit'));
});

test('headline has a maximum length of 255 characters', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => str_repeat('a', 256),
        'bio' => 'A passionate developer.',
    ])->assertInvalid('headline');

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => str_repeat('a', 255),
        'bio' => 'A passionate developer.',
    ])->assertRedirect(route('dashboard.profile.edit'));
});

test('github_url can be omitted', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
    ])->assertRedirect(route('dashboard.profile.edit'));
});

test('github_url must be a valid url', function () {
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'github_url' => 'not-a-valid-url',
    ])->assertInvalid('github_url');
});

test('github_url accepts valid urls', function (string $url) {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'github_url' => $url,
    ])->assertRedirect(route('dashboard.profile.edit'));
})->with([
    'https://github.com/johndoe',
    'https://github.com/johndoe/repo',
    'http://github.com/johndoe',
]);

test('github_url has a maximum length of 255 characters', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'github_url' => 'https://github.com/'.str_repeat('a', 240),
    ])->assertInvalid('github_url');

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'github_url' => 'https://github.com/'.str_repeat('a', 223),
    ])->assertRedirect(route('dashboard.profile.edit'));
});

test('linkedin_url can be omitted', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
    ])->assertRedirect(route('dashboard.profile.edit'));
});

test('linkedin_url must be a valid url', function () {
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'linkedin_url' => 'not-a-valid-url',
    ])->assertInvalid('linkedin_url');
});

test('linkedin_url accepts valid urls', function (string $url) {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'linkedin_url' => $url,
    ])->assertRedirect(route('dashboard.profile.edit'));
})->with([
    'https://linkedin.com/in/johndoe',
    'https://www.linkedin.com/in/johndoe',
    'http://linkedin.com/in/johndoe',
]);

test('linkedin_url has a maximum length of 255 characters', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'linkedin_url' => 'https://linkedin.com/in/'.str_repeat('a', 232),
    ])->assertInvalid('linkedin_url');

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'linkedin_url' => 'https://linkedin.com/in/'.str_repeat('a', 231),
    ])->assertRedirect(route('dashboard.profile.edit'));
});

test('valid complete data passes validation and updates profile', function () {
    Profile::factory()->create([
        'name' => 'Old Name',
        'headline' => 'Old Headline',
        'bio' => 'Old bio.',
    ]);
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Senior Software Engineer',
        'bio' => 'A passionate developer with 10 years of experience.',
        'location' => 'San Francisco, CA',
        'github_url' => 'https://github.com/johndoe',
        'linkedin_url' => 'https://linkedin.com/in/johndoe',
    ])->assertRedirect(route('dashboard.profile.edit'));

    $profile = Profile::current();
    expect($profile->name)->toBe('John Doe')
        ->and($profile->headline)->toBe('Senior Software Engineer')
        ->and($profile->bio)->toBe('A passionate developer with 10 years of experience.')
        ->and($profile->location)->toBe('San Francisco, CA')
        ->and($profile->github_url)->toBe('https://github.com/johndoe')
        ->and($profile->linkedin_url)->toBe('https://linkedin.com/in/johndoe');
});

test('nullable fields can be set to null', function () {
    Profile::factory()->create([
        'location' => 'Old Location',
        'github_url' => 'https://github.com/old',
        'linkedin_url' => 'https://linkedin.com/in/old',
    ]);
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'John Doe',
        'headline' => 'Software Engineer',
        'bio' => 'A passionate developer.',
        'location' => null,
        'github_url' => null,
        'linkedin_url' => null,
    ])->assertRedirect(route('dashboard.profile.edit'));

    $profile = Profile::current();
    expect($profile->location)->toBeNull()
        ->and($profile->github_url)->toBeNull()
        ->and($profile->linkedin_url)->toBeNull();
});

test('request uses default authorization (no authorize method defined)', function () {
    $request = new UpdateProfileRequest;

    // In Laravel 11, FormRequest doesn't define authorize() by default.
    // Authorization is handled via route middleware or policies.
    expect(method_exists($request, 'authorize'))->toBeFalse();
});
