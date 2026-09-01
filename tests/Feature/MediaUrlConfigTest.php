<?php

use App\Models\Media;
use App\Models\Post;
use App\Models\Project;
use App\Models\ProjectScreenshot;
use Illuminate\Support\Facades\Storage;

/**
 * Verify that the MEDIA_URL env var (mapped to the media disk's `url`
 * config) controls the base URL for all public media URLs, decoupling
 * the read path from the S3 write endpoint.
 */
test('media disk url is driven by MEDIA_URL env var', function () {
    expect(config('filesystems.disks.media.url'))->toBe(env('MEDIA_URL'));
});

test('post cover url uses the media disk url base', function () {
    $cdnBase = 'https://media.walfa.my.id';
    config(['filesystems.disks.media' => [
        'driver' => 'local',
        'root' => storage_path('app/testing/media'),
        'url' => $cdnBase,
        'bucket' => 'walfa-media',
    ]]);

    $post = Post::factory()->make(['cover_image_path' => 'posts/cover.png']);

    expect($post->cover_url)->toStartWith($cdnBase)
        ->and($post->cover_url)->toContain('posts/cover.png');
});

test('media model url uses the media disk url base', function () {
    $cdnBase = 'https://media.walfa.my.id';
    config(['filesystems.disks.media' => [
        'driver' => 'local',
        'root' => storage_path('app/testing/media'),
        'url' => $cdnBase,
        'bucket' => 'walfa-media',
    ]]);

    $media = Media::factory()->make(['path' => 'uploads/photo.jpg']);

    expect($media->url)->toStartWith($cdnBase)
        ->and($media->url)->toContain('uploads/photo.jpg');
});

test('project screenshot url uses the media disk url base', function () {
    $cdnBase = 'https://media.walfa.my.id';
    config(['filesystems.disks.media' => [
        'driver' => 'local',
        'root' => storage_path('app/testing/media'),
        'url' => $cdnBase,
        'bucket' => 'walfa-media',
    ]]);

    $project = Project::factory()->create();
    $screenshot = ProjectScreenshot::factory()->make([
        'project_id' => $project->id,
        'path' => 'projects/shot.png',
    ]);

    expect($screenshot->url)->toStartWith($cdnBase)
        ->and($screenshot->url)->toContain('projects/shot.png');
});

test('changing MEDIA_URL changes all public URLs consistently', function () {
    $project = Project::factory()->create();

    // First CDN domain — fresh model instances
    config(['filesystems.disks.media' => [
        'driver' => 'local',
        'root' => storage_path('app/testing/media'),
        'url' => 'https://cdn-a.example.com',
        'bucket' => 'walfa-media',
    ]]);

    $post = Post::factory()->make(['cover_image_path' => 'posts/cover.png']);
    $media = Media::factory()->make(['path' => 'uploads/photo.jpg']);
    $screenshot = ProjectScreenshot::factory()->make([
        'project_id' => $project->id,
        'path' => 'projects/shot.png',
    ]);

    expect($post->cover_url)->toStartWith('https://cdn-a.example.com')
        ->and($media->url)->toStartWith('https://cdn-a.example.com')
        ->and($screenshot->url)->toStartWith('https://cdn-a.example.com');

    // Switch to another CDN domain — fresh instances pick up the new URL
    config(['filesystems.disks.media' => [
        'driver' => 'local',
        'root' => storage_path('app/testing/media'),
        'url' => 'https://cdn-b.example.com',
        'bucket' => 'walfa-media',
    ]]);
    Storage::purge('media');

    $post2 = Post::factory()->make(['cover_image_path' => 'posts/cover.png']);
    $media2 = Media::factory()->make(['path' => 'uploads/photo.jpg']);
    $screenshot2 = ProjectScreenshot::factory()->make([
        'project_id' => $project->id,
        'path' => 'projects/shot.png',
    ]);

    expect($post2->cover_url)->toStartWith('https://cdn-b.example.com')
        ->and($media2->url)->toStartWith('https://cdn-b.example.com')
        ->and($screenshot2->url)->toStartWith('https://cdn-b.example.com');
});
