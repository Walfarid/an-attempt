<?php

use App\Models\Project;
use App\Models\ProjectScreenshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2025-08-26 12:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('factory creates a valid project screenshot', function () {
    $screenshot = ProjectScreenshot::factory()->create();

    expect($screenshot)->toBeInstanceOf(ProjectScreenshot::class)
        ->and($screenshot->project_id)->toBeInt()
        ->and($screenshot->path)->toStartWith('projects/')
        ->and($screenshot->path)->toEndWith('.png')
        ->and($screenshot->alt)->toBeString()
        ->and($screenshot->sort_order)->toBeInt()
        ->and($screenshot->created_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('factory creates a screenshot belonging to a project', function () {
    $project = Project::factory()->create();
    $screenshot = ProjectScreenshot::factory()->create(['project_id' => $project->id]);

    expect($screenshot->project_id)->toBe($project->id)
        ->and($screenshot->project->id)->toBe($project->id);
});

test('factory path contains a uuid', function () {
    $screenshot = ProjectScreenshot::factory()->create();

    // Path format: projects/{uuid}.png
    $pathParts = explode('/', $screenshot->path);
    expect($pathParts)->toHaveCount(2)
        ->and($pathParts[0])->toBe('projects');

    $filename = $pathParts[1];
    $uuid = str_replace('.png', '', $filename);
    expect(strlen($uuid))->toBe(36) // UUID v4 format
        ->and($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('fillable attributes are mass assignable', function () {
    $project = Project::factory()->create();

    $screenshot = ProjectScreenshot::create([
        'project_id' => $project->id,
        'path' => 'projects/custom-screenshot.png',
        'alt' => 'A custom alt text',
        'sort_order' => 5,
    ]);

    expect($screenshot->project_id)->toBe($project->id)
        ->and($screenshot->path)->toBe('projects/custom-screenshot.png')
        ->and($screenshot->alt)->toBe('A custom alt text')
        ->and($screenshot->sort_order)->toBe(5);
});

test('id and timestamps are not mass assignable', function () {
    $project = Project::factory()->create();

    $screenshot = ProjectScreenshot::create([
        'project_id' => $project->id,
        'path' => 'projects/test.png',
        'id' => 999,
    ]);

    expect($screenshot->id)->not->toBe(999);
});

test('url is appended to model serialization', function () {
    Storage::fake('media');
    config(['filesystems.disks.media.bucket' => 'test-bucket']);

    $screenshot = ProjectScreenshot::factory()->create();

    expect($screenshot->toArray())->toHaveKey('url');
});

test('url attribute returns null when media disk has no bucket configured', function () {
    config(['filesystems.disks.media.bucket' => null]);

    $screenshot = ProjectScreenshot::factory()->make(['path' => 'projects/test.png']);

    expect($screenshot->url)->toBeNull();
});

test('url attribute returns storage url when media disk is configured', function () {
    Storage::fake('media');
    config(['filesystems.disks.media.bucket' => 'my-bucket']);

    $screenshot = ProjectScreenshot::factory()->make(['path' => 'projects/screenshot.png']);

    expect($screenshot->url)->toContain('projects/screenshot.png');
});

test('url attribute uses the media disk', function () {
    Storage::fake('media');
    config(['filesystems.disks.media.bucket' => 'portfolio-media']);

    $screenshot = ProjectScreenshot::factory()->make(['path' => 'projects/image.png']);

    // The URL should be generated from the media disk
    expect($screenshot->url)->not->toBeNull();
});

test('project relationship returns the parent project', function () {
    $project = Project::factory()->create(['title' => 'Test Project']);
    $screenshot = ProjectScreenshot::factory()->create(['project_id' => $project->id]);

    expect($screenshot->project)->toBeInstanceOf(Project::class)
        ->and($screenshot->project->id)->toBe($project->id)
        ->and($screenshot->project->title)->toBe('Test Project');
});

test('project relationship is a belongsTo relationship', function () {
    $screenshot = new ProjectScreenshot;

    $relation = $screenshot->project();

    expect($relation)->toBeInstanceOf(BelongsTo::class);
});

test('deleting a project cascades to screenshots', function () {
    $project = Project::factory()->create();
    $screenshot = ProjectScreenshot::factory()->create(['project_id' => $project->id]);

    expect(ProjectScreenshot::where('id', $screenshot->id)->exists())->toBeTrue();

    $project->delete();

    expect(ProjectScreenshot::where('id', $screenshot->id)->exists())->toBeFalse();
});

test('alt attribute can be null', function () {
    $screenshot = ProjectScreenshot::factory()->create(['alt' => null]);

    expect($screenshot->fresh()->alt)->toBeNull();
});

test('alt attribute can be an empty string', function () {
    $screenshot = ProjectScreenshot::factory()->create(['alt' => '']);

    expect($screenshot->fresh()->alt)->toBe('');
});

test('sort_order defaults to zero via factory', function () {
    $screenshot = ProjectScreenshot::factory()->create();

    expect($screenshot->sort_order)->toBe(0);
});

test('sort_order defaults to zero via the database', function () {
    $project = Project::factory()->create();

    $screenshot = ProjectScreenshot::create([
        'project_id' => $project->id,
        'path' => 'projects/default-order.png',
    ]);

    expect($screenshot->fresh()->sort_order)->toBe(0);
});

test('sort_order can be set to any non-negative integer', function () {
    $screenshot = ProjectScreenshot::factory()->create(['sort_order' => 42]);

    expect($screenshot->sort_order)->toBe(42);
});

test('path is required', function () {
    $project = Project::factory()->create();

    $screenshot = new ProjectScreenshot([
        'project_id' => $project->id,
        // path is missing
    ]);

    expect(fn () => $screenshot->save())->toThrow(QueryException::class);
});

test('project_id is required', function () {
    $screenshot = new ProjectScreenshot([
        'path' => 'projects/orphan.png',
        // project_id is missing
    ]);

    expect(fn () => $screenshot->save())->toThrow(QueryException::class);
});

test('multiple screenshots can belong to the same project', function () {
    $project = Project::factory()->create();

    $screenshot1 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 1]);
    $screenshot2 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 2]);
    $screenshot3 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 3]);

    expect($project->screenshots)->toHaveCount(3)
        ->and($project->screenshots->pluck('id')->toArray())
        ->toContain($screenshot1->id, $screenshot2->id, $screenshot3->id);
});

test('screenshots are ordered by sort_order on the project relationship', function () {
    $project = Project::factory()->create();

    // Create screenshots with non-sequential sort orders
    $screenshot3 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 30]);
    $screenshot1 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 10]);
    $screenshot2 = ProjectScreenshot::factory()->create(['project_id' => $project->id, 'sort_order' => 20]);

    // Refresh the project to get fresh relationship data
    $project->refresh();

    $orderedIds = $project->screenshots->pluck('id')->toArray();

    expect($orderedIds)->toBe([$screenshot1->id, $screenshot2->id, $screenshot3->id]);
});

test('created_at and updated_at are automatically set', function () {
    $screenshot = ProjectScreenshot::factory()->create();

    expect($screenshot->created_at)->not->toBeNull()
        ->and($screenshot->updated_at)->not->toBeNull()
        ->and($screenshot->created_at->format('Y-m-d'))->toBe('2025-08-26');
});

test('timestamps are updated when model is modified', function () {
    $screenshot = ProjectScreenshot::factory()->create();
    $originalUpdatedAt = $screenshot->updated_at;

    // Travel forward in time
    CarbonImmutable::setTestNow('2025-08-27 10:00:00');

    $screenshot->update(['alt' => 'Updated alt text']);

    expect($screenshot->fresh()->updated_at->format('Y-m-d'))->toBe('2025-08-27');
});

test('url attribute is read-only (has no setter)', function () {
    $screenshot = ProjectScreenshot::factory()->create();

    // Attempting to set the url attribute should not throw but should not persist
    $screenshot->url = 'https://example.com/fake-url.png';

    // The url is derived from path, not stored
    expect($screenshot->url)->not->toBe('https://example.com/fake-url.png');
});

test('model serializes correctly to array', function () {
    Storage::fake('media');
    config(['filesystems.disks.media.bucket' => 'test-bucket']);

    $screenshot = ProjectScreenshot::factory()->create([
        'alt' => 'Test alt text',
        'sort_order' => 5,
    ]);

    $array = $screenshot->toArray();

    expect($array)->toHaveKey('id')
        ->and($array)->toHaveKey('project_id')
        ->and($array)->toHaveKey('path')
        ->and($array)->toHaveKey('alt')
        ->and($array)->toHaveKey('sort_order')
        ->and($array)->toHaveKey('url')
        ->and($array)->toHaveKey('created_at')
        ->and($array)->toHaveKey('updated_at');
});

test('model serializes correctly to json', function () {
    Storage::fake('media');
    config(['filesystems.disks.media.bucket' => 'test-bucket']);

    $screenshot = ProjectScreenshot::factory()->create([
        'path' => 'projects/test.png',
        'alt' => 'Alt text',
    ]);

    $json = json_encode($screenshot);
    $decoded = json_decode($json, true);

    expect($decoded)->toHaveKey('path')
        ->and($decoded['path'])->toBe('projects/test.png')
        ->and($decoded)->toHaveKey('url');
});
