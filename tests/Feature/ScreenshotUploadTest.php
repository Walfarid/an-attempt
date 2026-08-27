<?php

use App\Models\Project;
use App\Models\ProjectScreenshot;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests cannot upload screenshots', function () {
    $project = Project::factory()->create();

    $this->post("/dashboard/projects/{$project->id}/screenshots", [])
        ->assertRedirect('/login');
});

test('users can upload a screenshot', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $image = UploadedFile::fake()->image('dashboard.png', 1200, 800);

    $this->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => $image,
        'alt' => 'Dashboard view',
    ])->assertRedirect(route('dashboard.projects.index'));

    $screenshot = ProjectScreenshot::where('project_id', $project->id)->first();

    expect($screenshot)->not->toBeNull()
        ->and($screenshot->alt)->toBe('Dashboard view')
        ->and($screenshot->sort_order)->toBe(1)
        ->and(Storage::disk('media')->exists($screenshot->path))->toBeTrue();
});

test('screenshots get an increasing sort order', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    foreach ([1, 2] as $i) {
        $this->post("/dashboard/projects/{$project->id}/screenshots", [
            'image' => UploadedFile::fake()->image("shot-{$i}.png"),
        ])->assertRedirect(route('dashboard.projects.index'));
    }

    expect($project->screenshots()->pluck('sort_order')->all())->toEqual([1, 2]);
});

test('uploads must be images', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->from('/dashboard/projects')->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
    ])->assertInvalid('image');
});

test('uploads must be jpg, jpeg, png, or webp', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    // GIF is not in the allowed mimes list
    $this->from('/dashboard/projects')->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('animation.gif', 100, 100),
    ])->assertInvalid('image');
});

test('uploads have a maximum size of 4096 kilobytes', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    // Create an image larger than 4096 KB (4.1 MB)
    $this->from('/dashboard/projects')->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('large.jpg')->size(4100),
    ])->assertInvalid('image');
});

test('image is required', function () {
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->from('/dashboard/projects')->post("/dashboard/projects/{$project->id}/screenshots", [
        'alt' => 'A screenshot',
    ])->assertInvalid('image');
});

test('alt must be a string', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->from('/dashboard/projects')->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.png'),
        'alt' => ['array', 'not', 'string'],
    ])->assertInvalid('alt');
});

test('alt has a maximum length of 255 characters', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->from('/dashboard/projects')->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.png'),
        'alt' => str_repeat('a', 256),
    ])->assertInvalid('alt');
});

test('alt can be omitted', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.png'),
    ])->assertRedirect(route('dashboard.projects.index'));
});

test('alt can be null', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.png'),
        'alt' => null,
    ])->assertRedirect(route('dashboard.projects.index'));
});

test('validates jpg images successfully', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.jpg', 1920, 1080),
    ])->assertRedirect(route('dashboard.projects.index'));
});

test('validates jpeg images successfully', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.jpeg', 800, 600),
    ])->assertRedirect(route('dashboard.projects.index'));
});

test('validates png images successfully', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.png', 1200, 800),
    ])->assertRedirect(route('dashboard.projects.index'));
});

test('validates webp images successfully', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.webp', 1600, 900),
    ])->assertRedirect(route('dashboard.projects.index'));
});

test('accepts image at exactly 4096 kilobytes', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.png')->size(4096),
    ])->assertRedirect(route('dashboard.projects.index'));
});

test('accepts alt at exactly 255 characters', function () {
    Storage::fake('media');
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->post("/dashboard/projects/{$project->id}/screenshots", [
        'image' => UploadedFile::fake()->image('screenshot.png'),
        'alt' => str_repeat('a', 255),
    ])->assertRedirect(route('dashboard.projects.index'));
});

test('users can delete a screenshot and its file', function () {
    Storage::fake('media');
    $project = Project::factory()->hasScreenshots()->create();
    $screenshot = $project->screenshots->first();
    Storage::disk('media')->put($screenshot->path, 'fake-image-bytes');
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/projects/{$project->id}/screenshots/{$screenshot->id}")
        ->assertRedirect(route('dashboard.projects.index'));

    expect(ProjectScreenshot::find($screenshot->id))->toBeNull()
        ->and(Storage::disk('media')->exists($screenshot->path))->toBeFalse();
});
