<?php

use App\Models\Experience;
use App\Models\User;

test('guests are redirected from the experience pages', function () {
    $this->get('/dashboard/experience')->assertRedirect('/login');

    $this->post('/dashboard/experience', [])->assertRedirect('/login');
});

test('users see the experience list', function () {
    Experience::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/experience')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Experience'));
});

test('users can create an experience', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/experience', [
        'role' => 'Software Developer',
        'company' => 'Awan Teknologi',
        'location' => 'Jakarta',
        'started_at' => '2019-02-01',
        'ended_at' => null,
        'summary' => 'Owned application development end to end.',
        'highlights' => ['Built REST APIs', 'Ran Kubernetes'],
    ])->assertRedirect(route('dashboard.experience.index'));

    $experience = Experience::where('role', 'Software Developer')->first();

    expect($experience)->not->toBeNull()
        ->and($experience->ended_at)->toBeNull()
        ->and($experience->highlights)->toBe(['Built REST APIs', 'Ran Kubernetes']);
});

test('experience dates must be ordered', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/experience', [
        'role' => 'Software Developer',
        'company' => 'Awan Teknologi',
        'location' => 'Jakarta',
        'started_at' => '2020-01-01',
        'ended_at' => '2019-01-01',
        'summary' => 'Impossible timeline.',
    ])->assertInvalid('ended_at');
});

test('users can update an experience', function () {
    $experience = Experience::factory()->current()->create();
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/experience/{$experience->id}", [
        'role' => $experience->role,
        'company' => $experience->company,
        'location' => $experience->location,
        'started_at' => $experience->started_at->toDateString(),
        'ended_at' => '2025-07-31',
        'summary' => $experience->summary,
        'highlights' => $experience->highlights,
    ])->assertRedirect(route('dashboard.experience.index'));

    expect($experience->fresh()->ended_at?->toDateString())->toBe('2025-07-31');
});

test('users can delete an experience', function () {
    $experience = Experience::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/experience/{$experience->id}")
        ->assertRedirect(route('dashboard.experience.index'));

    expect(Experience::find($experience->id))->toBeNull();
});
