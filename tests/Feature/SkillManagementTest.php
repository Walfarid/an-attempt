<?php

use App\Models\Skill;
use App\Models\User;

test('guests are redirected from the skills pages', function () {
    $this->get('/dashboard/skills')->assertRedirect('/login');

    $this->post('/dashboard/skills', ['name' => 'Go', 'category' => 'languages'])
        ->assertRedirect('/login');
});

test('users see the skills list', function () {
    $skill = Skill::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/skills')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Skills'));
});

test('users can create a skill', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/skills', [
        'name' => 'Rust',
        'category' => 'languages',
    ])->assertRedirect(route('dashboard.skills.index'));

    expect(Skill::where('name', 'Rust')->exists())->toBeTrue();
});

test('skill names must be unique per category', function () {
    Skill::factory()->create(['name' => 'Go', 'category' => 'languages']);
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/skills', [
        'name' => 'Go',
        'category' => 'languages',
    ])->assertInvalid('name');

    // Same name in another category is allowed.
    $this->from('/dashboard/skills')->post('/dashboard/skills', [
        'name' => 'Go',
        'category' => 'platform',
    ])->assertRedirect(route('dashboard.skills.index'));

    expect(Skill::where('name', 'Go')->count())->toBe(2);
});

test('skill category must be a known value', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/skills', [
        'name' => 'Rust',
        'category' => 'not-a-category',
    ])->assertInvalid('category');
});

test('users can update a skill', function () {
    $skill = Skill::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/skills/{$skill->id}", [
        'name' => 'Go 2',
        'category' => 'languages',
    ])->assertRedirect(route('dashboard.skills.index'));

    expect($skill->fresh()->name)->toBe('Go 2');
});

test('users can delete a skill', function () {
    $skill = Skill::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/skills/{$skill->id}")
        ->assertRedirect(route('dashboard.skills.index'));

    expect(Skill::find($skill->id))->toBeNull();
});
