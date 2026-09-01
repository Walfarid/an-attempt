<?php

use App\Models\Education;
use App\Models\Experience;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Publication;
use App\Models\Skill;

test('the home page renders for guests', function () {
    Profile::factory()->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Welcome')
            ->has('profile')
            ->has('stats.years_active')
            ->has('stats.projects_count')
            ->has('stats.skills_count')
            ->has('contact_email')
            ->loadDeferredProps('default', fn ($page) => $page
                ->has('experiences')
                ->has('skills')
                ->has('projects')
                ->has('educations')
                ->has('publications')
                ->has('posts')
            ));
});

test('the home page only shows published projects and posts', function () {
    Profile::factory()->create();
    Project::factory()->create(['title' => 'Published project']);
    Project::factory()->draft()->create(['title' => 'Hidden draft']);
    Post::factory()->create(['title' => 'Published post']);
    Post::factory()->draft()->create(['title' => 'Hidden post']);

    $this->get('/')->assertInertia(function ($page) {
        $page->loadDeferredProps('default', function ($page) {
            $props = $page->toArray()['props'];
            $projectTitles = array_column($props['projects'], 'title');
            $postTitles = array_column($props['posts'], 'title');

            expect($projectTitles)->toContain('Published project')->not->toContain('Hidden draft')
                ->and($postTitles)->toContain('Published post')->not->toContain('Hidden post');
        });
    });
});

test('the home page includes portfolio content', function () {
    Profile::factory()->create([
        'bio' => 'Calm **reliable** software.',
    ]);
    Experience::factory()->count(2)->create();
    Skill::factory()->count(3)->create();
    Education::factory()->count(2)->create();
    Publication::factory()->count(2)->create();

    $this->get('/')->assertInertia(function ($page) {
        expect($page->toArray()['props']['profile']['bio_html'])->toContain('<strong>reliable</strong>');

        $page->loadDeferredProps('default', function ($page) {
            $props = $page->toArray()['props'];

            expect(count($props['experiences']))->toBe(2)
                ->and(count($props['skills']))->toBe(3)
                ->and(count($props['educations']))->toBe(2)
                ->and(count($props['publications']))->toBe(2);
        });
    });
});

test('home projects carry their skills and screenshot urls', function () {
    Storage::fake('media');
    Profile::factory()->create();
    $project = Project::factory()->has(Skill::factory()->count(2), 'skills')->create();
    $screenshot = $project->screenshots()->create([
        'path' => 'projects/1/shot.png',
        'alt' => null,
        'sort_order' => 1,
    ]);

    $this->get('/')->assertInertia(function ($page) use ($project, $screenshot) {
        $page->loadDeferredProps('default', function ($page) use ($project, $screenshot) {
            $props = $page->toArray()['props'];
            $first = collect($props['projects'])->firstWhere('id', $project->id);

            expect(count($first['skills']))->toBe(2)
                ->and($first['screenshots'][0]['url'])->toBe(Storage::disk('media')->url($screenshot->path))
                ->and($first['screenshots'][0])->not->toHaveKey('id');
        });
    });
});
