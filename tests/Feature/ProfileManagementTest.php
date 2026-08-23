<?php

use App\Models\Profile;
use App\Models\User;

test('guests are redirected from the profile form', function () {
    $this->get('/dashboard/profile/edit')->assertRedirect('/login');
});

test('authenticated users see the profile edit form', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/profile/edit')->assertOk();
});

test('users can update the profile', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'name' => 'Walfa',
        'headline' => 'Full-stack Developer',
        'bio' => '# Hello\n\nI build things.',
        'location' => 'Jakarta',
        'github_url' => 'https://github.com/walfa',
        'linkedin_url' => null,
    ])->assertRedirect(route('dashboard.profile.edit'));

    expect(Profile::current()->name)->toBe('Walfa')
        ->and(Profile::current()->location)->toBe('Jakarta');
});

test('profile update requires valid data', function () {
    Profile::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put('/dashboard/profile', [
        'headline' => 'Full-stack Developer',
        'bio' => 'bio',
        'github_url' => 'not-a-url',
    ])->assertInvalid(['name', 'github_url']);
});
