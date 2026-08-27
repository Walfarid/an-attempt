<?php

use App\Models\Project;
use App\Models\ProjectScreenshot;
use App\Models\Skill;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2025-08-26 12:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('factory creates a valid project', function () {
    $project = Project::factory()->create();

    expect($project)->toBeInstanceOf(Project::class)
        ->and($project->slug)->not->toBeEmpty()
        ->and($project->title)->not->toBeEmpty()
        ->and($project->description)->not->toBeEmpty()
        ->and($project->year)->toBeInt()
        ->and($project->published_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('factory draft state creates an unpublished project', function () {
    $project = Project::factory()->draft()->create();

    expect($project->published_at)->toBeNull();
});

test('factory featured state creates a featured project', function () {
    $project = Project::factory()->featured()->create();

    expect($project->featured)->toBeTrue();
});

test('factory default state creates a non-featured project', function () {
    $project = Project::factory()->create();

    expect($project->featured)->toBeFalse();
});

test('fillable attributes are mass assignable', function () {
    $project = Project::create([
        'slug' => 'test-project',
        'title' => 'Test Project Title',
        'description' => 'A test project description.',
        'year' => 2025,
        'live_url' => 'https://example.com',
        'repo_url' => 'https://github.com/example/repo',
        'image_tone' => 'from-blue-500/20 via-purple-500/10 to-transparent',
        'featured' => true,
        'sort_order' => 10,
        'published_at' => '2025-08-20 10:00:00',
    ]);

    expect($project->slug)->toBe('test-project')
        ->and($project->title)->toBe('Test Project Title')
        ->and($project->description)->toBe('A test project description.')
        ->and($project->year)->toBe(2025)
        ->and($project->live_url)->toBe('https://example.com')
        ->and($project->repo_url)->toBe('https://github.com/example/repo')
        ->and($project->image_tone)->toBe('from-blue-500/20 via-purple-500/10 to-transparent')
        ->and($project->featured)->toBeTrue()
        ->and($project->sort_order)->toBe(10)
        ->and($project->published_at->format('Y-m-d'))->toBe('2025-08-20');
});

test('id and timestamps are not mass assignable', function () {
    $project = Project::create([
        'slug' => 'protected-attributes',
        'title' => 'Protected Attributes',
        'description' => 'Testing mass assignment.',
        'year' => 2025,
        'id' => 999,
    ]);

    expect($project->id)->not->toBe(999);
});

test('year is cast to integer', function () {
    $project = Project::factory()->create(['year' => '2025']);

    expect($project->year)->toBeInt()
        ->and($project->year)->toBe(2025);
});

test('featured is cast to boolean', function () {
    $project = Project::factory()->create(['featured' => 1]);

    expect($project->featured)->toBeBool()
        ->and($project->featured)->toBeTrue();
});

test('published_at is cast to datetime', function () {
    $project = Project::factory()->create(['published_at' => '2025-08-15 09:00:00']);

    expect($project->published_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($project->published_at->format('Y-m-d H:i:s'))->toBe('2025-08-15 09:00:00');
});

test('published_at can be null', function () {
    $project = Project::factory()->create(['published_at' => null]);

    expect($project->fresh()->published_at)->toBeNull();
});

test('live_url can be null', function () {
    $project = Project::factory()->create(['live_url' => null]);

    expect($project->fresh()->live_url)->toBeNull();
});

test('repo_url can be null', function () {
    $project = Project::factory()->create(['repo_url' => null]);

    expect($project->fresh()->repo_url)->toBeNull();
});

test('image_tone can be null', function () {
    $project = Project::factory()->create(['image_tone' => null]);

    expect($project->fresh()->image_tone)->toBeNull();
});

test('scopePublished returns only published projects', function () {
    $published = Project::factory()->create([
        'title' => 'Published Project',
        'published_at' => now()->subDay(),
    ]);

    $draft = Project::factory()->draft()->create(['title' => 'Draft Project']);

    $future = Project::factory()->create([
        'title' => 'Future Project',
        'published_at' => now()->addWeek(),
    ]);

    $results = Project::published()->pluck('title')->toArray();

    expect($results)->toContain('Published Project')
        ->and($results)->not->toContain('Draft Project')
        ->and($results)->not->toContain('Future Project');
});

test('scopePublished includes projects published at the current moment', function () {
    $now = now();
    $project = Project::factory()->create(['published_at' => $now]);

    expect(Project::published()->where('id', $project->id)->exists())->toBeTrue();
});

test('scopePublished excludes projects with future published_at dates', function () {
    $future = Project::factory()->create(['published_at' => now()->addSecond()]);

    expect(Project::published()->where('id', $future->id)->exists())->toBeFalse();
});

test('scopePublished excludes projects with null published_at', function () {
    $draft = Project::factory()->draft()->create();

    expect(Project::published()->where('id', $draft->id)->exists())->toBeFalse();
});

test('skills relationship returns related skills', function () {
    $project = Project::factory()->create();
    $skills = Skill::factory()->count(3)->create();

    $project->skills()->attach($skills);

    expect($project->skills)->toHaveCount(3)
        ->and($project->skills->first())->toBeInstanceOf(Skill::class);
});

test('skills relationship is a belongsToMany relationship', function () {
    $project = new Project;

    $relation = $project->skills();

    expect($relation)->toBeInstanceOf(BelongsToMany::class);
});

test('skills can be synced to a project', function () {
    $project = Project::factory()->create();
    $skills = Skill::factory()->count(2)->create();

    $project->skills()->sync($skills->pluck('id'));

    expect($project->fresh()->skills)->toHaveCount(2);
});

test('skills can be detached from a project', function () {
    $project = Project::factory()->create();
    $skills = Skill::factory()->count(2)->create();

    $project->skills()->attach($skills);
    $project->skills()->detach($skills->first());

    expect($project->fresh()->skills)->toHaveCount(1);
});

test('deleting a project detaches associated skills', function () {
    $project = Project::factory()->create();
    $skills = Skill::factory()->count(2)->create();

    $project->skills()->attach($skills);

    $project->delete();

    // Skills should still exist
    expect(Skill::count())->toBe(2);

    // But the pivot records should be gone
    expect($project->skills()->count())->toBe(0);
});

test('screenshots relationship returns related screenshots in sort_order', function () {
    $project = Project::factory()->create();

    $screenshot3 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 30]);
    $screenshot1 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 10]);
    $screenshot2 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 20]);

    // Refresh to reload the relationship
    $project->refresh();

    $orderedIds = $project->screenshots->pluck('id')->toArray();

    expect($project->screenshots)->toHaveCount(3)
        ->and($orderedIds)->toBe([$screenshot1->id, $screenshot2->id, $screenshot3->id]);
});

test('screenshots relationship is a hasMany relationship', function () {
    $project = new Project;

    $relation = $project->screenshots();

    expect($relation)->toBeInstanceOf(HasMany::class);
});

test('screenshots relationship orders by sort_order', function () {
    $project = Project::factory()->create();

    $screenshotA = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 100]);
    $screenshotB = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 1]);
    $screenshotC = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 50]);

    $project->refresh();

    $order = $project->screenshots->pluck('sort_order')->toArray();

    expect($order)->toBe([1, 50, 100]);
});

test('deleting a project cascades to screenshots', function () {
    $project = Project::factory()->create();
    $screenshot = ProjectScreenshot::factory()->create(['project_id' => $project->id]);

    expect(ProjectScreenshot::where('id', $screenshot->id)->exists())->toBeTrue();

    $project->delete();

    expect(ProjectScreenshot::where('id', $screenshot->id)->exists())->toBeFalse();
});

test('slug must be unique', function () {
    Project::factory()->create(['slug' => 'unique-slug']);

    $duplicate = Project::factory()->make(['slug' => 'unique-slug']);

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);
});

test('slug is required', function () {
    $project = Project::factory()->make(['slug' => null]);

    expect(fn () => $project->save())->toThrow(QueryException::class);
});

test('title is required', function () {
    $project = Project::factory()->make(['title' => null]);

    expect(fn () => $project->save())->toThrow(QueryException::class);
});

test('description is required', function () {
    $project = Project::factory()->make(['description' => null]);

    expect(fn () => $project->save())->toThrow(QueryException::class);
});

test('year is required', function () {
    $project = Project::factory()->make(['year' => null]);

    expect(fn () => $project->save())->toThrow(QueryException::class);
});

test('sort_order defaults to zero via factory', function () {
    $project = Project::factory()->create();

    expect($project->sort_order)->toBe(0);
});

test('sort_order defaults to zero via the database', function () {
    $project = Project::create([
        'slug' => 'no-sort-order',
        'title' => 'No Sort Order',
        'description' => 'Testing default sort order.',
        'year' => 2025,
    ]);

    expect($project->fresh()->sort_order)->toBe(0);
});

test('sort_order can be set to any non-negative integer', function () {
    $project = Project::factory()->create(['sort_order' => 42]);

    expect($project->sort_order)->toBe(42);
});

test('featured defaults to false via the database', function () {
    $project = Project::create([
        'slug' => 'not-featured',
        'title' => 'Not Featured',
        'description' => 'Testing default featured.',
        'year' => 2025,
    ]);

    expect($project->fresh()->featured)->toBeFalse();
});

test('created_at and updated_at are automatically set', function () {
    $project = Project::factory()->create();

    expect($project->created_at)->not->toBeNull()
        ->and($project->updated_at)->not->toBeNull()
        ->and($project->created_at->format('Y-m-d'))->toBe('2025-08-26');
});

test('timestamps are updated when model is modified', function () {
    $project = Project::factory()->create();
    $originalUpdatedAt = $project->updated_at;

    // Travel forward in time
    CarbonImmutable::setTestNow('2025-08-27 10:00:00');

    $project->update(['title' => 'Updated Title']);

    expect($project->fresh()->updated_at->format('Y-m-d'))->toBe('2025-08-27');
});

test('factory generates unique slugs to avoid collisions', function () {
    $projects = Project::factory()->count(3)->create();

    $slugs = $projects->pluck('slug')->toArray();

    expect(count($slugs))->toBe(count(array_unique($slugs)));
});

test('factory respects explicitly set slug', function () {
    $project = Project::factory()->create(['slug' => 'explicit-slug']);

    expect($project->slug)->toBe('explicit-slug');
});

test('factory respects explicitly set title', function () {
    $project = Project::factory()->create(['title' => 'My Custom Title Here']);

    expect($project->title)->toBe('My Custom Title Here');
});

test('project can have both live_url and repo_url', function () {
    $project = Project::factory()->create([
        'live_url' => 'https://example.com',
        'repo_url' => 'https://github.com/example/repo',
    ]);

    expect($project->live_url)->toBe('https://example.com')
        ->and($project->repo_url)->toBe('https://github.com/example/repo');
});

test('project can have live_url without repo_url', function () {
    $project = Project::factory()->create([
        'live_url' => 'https://example.com',
        'repo_url' => null,
    ]);

    expect($project->live_url)->toBe('https://example.com')
        ->and($project->repo_url)->toBeNull();
});

test('project can have repo_url without live_url', function () {
    $project = Project::factory()->create([
        'live_url' => null,
        'repo_url' => 'https://github.com/example/repo',
    ]);

    expect($project->live_url)->toBeNull()
        ->and($project->repo_url)->toBe('https://github.com/example/repo');
});

test('project can have neither live_url nor repo_url', function () {
    $project = Project::factory()->create([
        'live_url' => null,
        'repo_url' => null,
    ]);

    expect($project->live_url)->toBeNull()
        ->and($project->repo_url)->toBeNull();
});

test('model serializes correctly to array', function () {
    $project = Project::factory()->create([
        'slug' => 'serialize-test',
        'title' => 'Serialize Test',
        'featured' => true,
    ]);

    $array = $project->toArray();

    expect($array)->toHaveKey('id')
        ->and($array)->toHaveKey('slug')
        ->and($array)->toHaveKey('title')
        ->and($array)->toHaveKey('description')
        ->and($array)->toHaveKey('year')
        ->and($array)->toHaveKey('live_url')
        ->and($array)->toHaveKey('repo_url')
        ->and($array)->toHaveKey('image_tone')
        ->and($array)->toHaveKey('featured')
        ->and($array)->toHaveKey('sort_order')
        ->and($array)->toHaveKey('published_at')
        ->and($array)->toHaveKey('created_at')
        ->and($array)->toHaveKey('updated_at');
});

test('model serializes correctly to json', function () {
    $project = Project::factory()->create([
        'slug' => 'json-test',
        'title' => 'JSON Test',
        'year' => 2025,
    ]);

    $json = json_encode($project);
    $decoded = json_decode($json, true);

    expect($decoded)->toHaveKey('slug')
        ->and($decoded['slug'])->toBe('json-test')
        ->and($decoded)->toHaveKey('title')
        ->and($decoded['title'])->toBe('JSON Test')
        ->and($decoded)->toHaveKey('year')
        ->and($decoded['year'])->toBe(2025);
});

test('multiple projects can share the same skill', function () {
    $skill = Skill::factory()->create();
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    $project1->skills()->attach($skill);
    $project2->skills()->attach($skill);

    expect($project1->fresh()->skills->first()->id)->toBe($skill->id)
        ->and($project2->fresh()->skills->first()->id)->toBe($skill->id);
});

test('project can have multiple screenshots with different sort orders', function () {
    $project = Project::factory()->create();

    $screenshot1 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 1]);
    $screenshot2 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 2]);
    $screenshot3 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 3]);

    expect($project->screenshots)->toHaveCount(3);
});

test('year can be a small integer', function () {
    $project = Project::factory()->create(['year' => 2018]);

    expect($project->year)->toBe(2018);
});

test('year can be a larger integer', function () {
    $project = Project::factory()->create(['year' => 2026]);

    expect($project->year)->toBe(2026);
});
