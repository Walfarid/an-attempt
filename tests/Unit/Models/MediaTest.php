<?php

use App\Models\Media;
use Carbon\CarbonImmutable;
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

test('factory creates a valid media record', function () {
    $media = Media::factory()->create();

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->name)->toBeString()
        ->and($media->path)->toBeString()
        ->and($media->mime)->toBeString()
        ->and($media->size)->toBeInt()
        ->and($media->created_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('fillable attributes are mass assignable', function () {
    $media = Media::create([
        'name' => 'photo.png',
        'path' => 'uploads/photo.png',
        'mime' => 'image/png',
        'size' => 12345,
    ]);

    expect($media->name)->toBe('photo.png')
        ->and($media->path)->toBe('uploads/photo.png')
        ->and($media->mime)->toBe('image/png')
        ->and($media->size)->toBe(12345);
});

test('id and timestamps are not mass assignable', function () {
    $media = Media::create([
        'name' => 'test.png',
        'path' => 'uploads/test.png',
        'mime' => 'image/png',
        'size' => 100,
        'id' => 999,
    ]);

    expect($media->id)->not->toBe(999);
});

test('url returns null when media disk has no bucket configured', function () {
    config(['filesystems.disks.media.bucket' => null]);

    $media = Media::factory()->make(['path' => 'uploads/image.png']);

    expect($media->url)->toBeNull();
});

test('url returns storage url when media disk is configured', function () {
    Storage::fake('media');
    config(['filesystems.disks.media.bucket' => 'my-bucket']);

    $media = Media::factory()->make(['path' => 'uploads/image.png']);

    expect($media->url)->toContain('uploads/image.png');
});

test('path must be unique', function () {
    Media::create([
        'name' => 'first',
        'path' => 'uploads/unique.png',
        'mime' => 'image/png',
        'size' => 100,
    ]);

    $duplicate = new Media([
        'name' => 'second',
        'path' => 'uploads/unique.png',
        'mime' => 'image/png',
        'size' => 200,
    ]);

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);
});

test('timestamps are automatically set on creation', function () {
    $media = Media::factory()->create();

    expect($media->created_at)->not->toBeNull()
        ->and($media->updated_at)->not->toBeNull()
        ->and($media->created_at->format('Y-m-d'))->toBe('2025-08-26');
});
