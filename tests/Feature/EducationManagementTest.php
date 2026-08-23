<?php

use App\Models\Education;
use App\Models\User;

test('guests are redirected from the educations pages', function () {
    $this->get('/dashboard/educations')->assertRedirect('/login');

    $this->post('/dashboard/educations', ['school' => 'NUS', 'degree' => 'M.Tech'])
        ->assertRedirect('/login');
});

test('users see the educations list', function () {
    Education::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/educations')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Educations'));
});

test('users can create an education record', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'National University of Singapore',
        'degree' => 'M.Tech in Software Engineering',
        'started_at' => '2025-08-01',
        'ended_at' => null,
        'details' => ['Focus on software architecture'],
    ])->assertRedirect(route('dashboard.educations.index'));

    expect(Education::where('school', 'National University of Singapore')->exists())->toBeTrue();
});

test('education requires school and degree', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'started_at' => '2025-08-01',
    ])->assertInvalid(['school', 'degree']);
});

test('education end date cannot precede the start date', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/educations', [
        'school' => 'NUS',
        'degree' => 'M.Tech',
        'started_at' => '2025-08-01',
        'ended_at' => '2024-01-01',
    ])->assertInvalid('ended_at');
});

test('users can update an education record', function () {
    $education = Education::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/educations/{$education->id}", [
        'school' => 'NUS',
        'degree' => 'M.Tech in Software Engineering',
        'started_at' => '2025-08-01',
        'ended_at' => null,
        'details' => [],
    ])->assertRedirect(route('dashboard.educations.index'));

    expect($education->fresh()->school)->toBe('NUS');
});

test('users can delete an education record', function () {
    $education = Education::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/educations/{$education->id}")
        ->assertRedirect(route('dashboard.educations.index'));

    expect(Education::find($education->id))->toBeNull();
});
