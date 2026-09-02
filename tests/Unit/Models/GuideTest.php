<?php

use App\Models\Guide;
use App\Models\Post;
use Carbon\CarbonImmutable;
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

test('guide has correct fillable fields', function () {
    $guide = Guide::create([
        'slug' => 'test-guide',
        'title' => 'Test Guide Title',
        'body' => 'Test guide body content.',
        'cover_image_path' => 'guides/cover.png',
        'published_at' => '2025-08-20 10:00:00',
        'teaser' => 'A hand-written teaser.',
        'prerequisites' => 'PHP and Composer.',
        'estimated_time' => '30 minutes',
    ]);

    expect($guide->slug)->toBe('test-guide')
        ->and($guide->title)->toBe('Test Guide Title')
        ->and($guide->body)->toBe('Test guide body content.')
        ->and($guide->cover_image_path)->toBe('guides/cover.png')
        ->and($guide->teaser)->toBe('A hand-written teaser.')
        ->and($guide->prerequisites)->toBe('PHP and Composer.')
        ->and($guide->estimated_time)->toBe('30 minutes')
        ->and($guide->published_at->format('Y-m-d'))->toBe('2025-08-20');
});

test('published_at casts to datetime', function () {
    $guide = Guide::factory()->create(['published_at' => '2025-08-15 09:00:00']);

    expect($guide->published_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($guide->published_at->format('Y-m-d H:i:s'))->toBe('2025-08-15 09:00:00');
});

test('scopePublished excludes drafts and future posts', function () {
    $published = Guide::factory()->create([
        'title' => 'Published Guide',
        'published_at' => now()->subDay(),
    ]);

    $draft = Guide::factory()->draft()->create(['title' => 'Draft Guide']);

    $future = Guide::factory()->create([
        'title' => 'Future Guide',
        'published_at' => now()->addWeek(),
    ]);

    $results = Guide::published()->pluck('title')->toArray();

    expect($results)->toContain('Published Guide')
        ->and($results)->not->toContain('Draft Guide')
        ->and($results)->not->toContain('Future Guide');
});

test('cover_url returns null when no cover', function () {
    $guide = Guide::factory()->make(['cover_image_path' => null]);

    expect($guide->cover_url)->toBeNull();
});

test('cover_url returns null when media bucket not configured', function () {
    config(['filesystems.disks.media.bucket' => null]);
    $guide = Guide::factory()->make(['cover_image_path' => 'guides/cover.png']);

    expect($guide->cover_url)->toBeNull();
});

test('cover_url returns storage url when cover and bucket exist', function () {
    Storage::fake('media');
    config(['filesystems.disks.media.bucket' => 'my-bucket']);

    $guide = Guide::factory()->make(['cover_image_path' => 'guides/cover.png']);

    expect($guide->cover_url)->toContain('guides/cover.png');
});

test('bodyHtml renders markdown', function () {
    $guide = Guide::factory()->make(['body' => "# Hello\n\nA paragraph with **bold** text."]);

    expect($guide->bodyHtml())->toContain('<h1>Hello</h1>')
        ->and($guide->bodyHtml())->toContain('<strong>bold</strong>');
});

test('teaser prefers hand-written teaser and falls back to body', function () {
    $withTeaser = Guide::factory()->make([
        'teaser' => 'Hand-written teaser.',
        'body' => str_repeat('word ', 60),
    ]);

    $withoutTeaser = Guide::factory()->make([
        'teaser' => null,
        'body' => str_repeat('word ', 60).'end',
    ]);

    expect($withTeaser->teaser())->toBe('Hand-written teaser.')
        ->and($withoutTeaser->teaser(10))->toStartWith('word word word')
        ->and(str_word_count($withoutTeaser->teaser(10)))->toBeLessThanOrEqual(11);
});

test('posts relationship works', function () {
    $guide = Guide::factory()->create();
    $posts = Post::factory()->count(3)->create();

    $guide->posts()->sync($posts->pluck('id'));

    expect($guide->posts()->count())->toBe(3)
        ->and($guide->posts->pluck('id')->toArray())->toEqualCanonicalizing($posts->pluck('id')->all());
});

test('factory creates valid guide', function () {
    $guide = Guide::factory()->create();

    expect($guide)->toBeInstanceOf(Guide::class)
        ->and($guide->slug)->not->toBeEmpty()
        ->and($guide->title)->not->toBeEmpty()
        ->and($guide->body)->not->toBeEmpty()
        ->and($guide->published_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and(Guide::where('slug', $guide->slug)->exists())->toBeTrue();
});

test('factory draft state sets published_at to null', function () {
    $guide = Guide::factory()->draft()->create();

    expect($guide->published_at)->toBeNull();
});

test('deleting guide detaches posts', function () {
    $guide = Guide::factory()->create();
    $posts = Post::factory()->count(2)->create();

    $guide->posts()->sync($posts->pluck('id'));

    expect($guide->posts()->count())->toBe(2);

    $guide->delete();

    expect($guide->posts()->count())->toBe(0)
        ->and(DB::table('guide_post')->count())->toBe(0)
        ->and(Post::whereIn('id', $posts->pluck('id'))->count())->toBe(2);
});
