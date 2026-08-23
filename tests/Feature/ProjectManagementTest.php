<?php

use App\Models\Project;
use App\Models\Skill;
use App\Models\User;

test('guests are redirected from the project pages', function () {
    $this->get('/dashboard/projects')->assertRedirect('/login');

    $this->post('/dashboard/projects', [])->assertRedirect('/login');
});

test('users see the projects list', function () {
    Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/projects')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Projects'));
});

test('users can create a project', function () {
    $this->actingAs(User::factory()->create());
    $skills = Skill::factory()->count(2)->create();

    $this->post('/dashboard/projects', [
        'title' => 'HR Platform',
        'description' => 'REST API backbone for an HR system.',
        'year' => 2021,
        'live_url' => null,
        'repo_url' => 'https://github.com/example/hr',
        'featured' => true,
        'skills' => $skills->pluck('id')->all(),
    ])->assertRedirect(route('dashboard.projects.index'));

    $project = Project::where('title', 'HR Platform')->first();

    expect($project->slug)->toBe('hr-platform')
        ->and($project->featured)->toBeTrue()
        ->and($project->skills->pluck('id')->sort()->values())->toEqual($skills->pluck('id')->sort()->values())
        ->and($project->published_at)->toBeNull();
});

test('users can set an explicit slug when creating', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/projects', [
        'title' => 'Security Integration Platform',
        'slug' => 'security-platform',
        'description' => 'Go services ingesting NVR data.',
        'year' => 2023,
    ])->assertRedirect(route('dashboard.projects.index'));

    expect(Project::where('slug', 'security-platform')->exists())->toBeTrue();
});

test('slugs must be unique', function () {
    Project::factory()->create(['slug' => 'hr-platform']);
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/projects', [
        'title' => 'Another Platform',
        'slug' => 'hr-platform',
        'description' => 'Duplicate slug.',
        'year' => 2024,
    ])->assertInvalid('slug');
});

test('users can update a project', function () {
    $project = Project::factory()->create();
    $skills = Skill::factory()->count(2)->create();
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/projects/{$project->id}", [
        'title' => 'Renamed Platform',
        'description' => $project->description,
        'year' => 2025,
        'featured' => false,
        'published_at' => '2026-01-15 10:00:00',
        'skills' => [$skills[0]->id],
    ])->assertRedirect(route('dashboard.projects.index'));

    $project->refresh();

    expect($project->title)->toBe('Renamed Platform')
        ->and($project->year)->toBe(2025)
        ->and($project->published_at?->format('Y-m-d'))->toBe('2026-01-15')
        ->and($project->skills->pluck('id'))->toEqual(collect([$skills[0]->id]));
});

test('users can delete a project', function () {
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/projects/{$project->id}")
        ->assertRedirect(route('dashboard.projects.index'));

    expect(Project::find($project->id))->toBeNull();
});

test('projects require valid data', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/projects', [
        'title' => 'Broken',
        'description' => 'desc',
        'year' => 123,
        'live_url' => 'not-a-url',
        'skills' => [99999],
    ])->assertInvalid(['year', 'live_url', 'skills.0']);
});
