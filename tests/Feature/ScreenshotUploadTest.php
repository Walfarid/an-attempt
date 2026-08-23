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
