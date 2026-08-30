<?php

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

test('factory creates a valid post', function () {
    $post = Post::factory()->create();

    expect($post)->toBeInstanceOf(Post::class)
        ->and($post->slug)->not->toBeEmpty()
        ->and($post->title)->not->toBeEmpty()
        ->and($post->body)->not->toBeEmpty()
        ->and($post->published_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('factory draft state creates an unpublished post', function () {
    $post = Post::factory()->draft()->create();

    expect($post->published_at)->toBeNull();
});

test('published_at is cast to a datetime', function () {
    $post = Post::factory()->create(['published_at' => '2025-08-15 09:00:00']);

    expect($post->published_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($post->published_at->format('Y-m-d H:i:s'))->toBe('2025-08-15 09:00:00');
});

test('published_at can be null', function () {
    $post = Post::factory()->create(['published_at' => null]);

    expect($post->fresh()->published_at)->toBeNull();
});

test('fillable attributes are mass assignable', function () {
    $post = Post::create([
        'slug' => 'test-post',
        'title' => 'Test Post Title',
        'excerpt' => 'A test excerpt.',
        'body' => 'Test body content.',
        'cover_image_path' => 'posts/cover.png',
        'published_at' => '2025-08-20 10:00:00',
    ]);

    expect($post->slug)->toBe('test-post')
        ->and($post->title)->toBe('Test Post Title')
        ->and($post->excerpt)->toBe('A test excerpt.')
        ->and($post->body)->toBe('Test body content.')
        ->and($post->cover_image_path)->toBe('posts/cover.png')
        ->and($post->published_at->format('Y-m-d'))->toBe('2025-08-20');
});

test('id and timestamps are not mass assignable', function () {
    $post = Post::create([
        'slug' => 'protected-attributes',
        'title' => 'Protected Attributes',
        'body' => 'Testing mass assignment.',
        'id' => 999,
    ]);

    expect($post->id)->not->toBe(999);
});

test('cover_url is not appended by default but can be added explicitly', function () {
    $post = Post::factory()->create();

    expect($post->toArray())->not->toHaveKey('cover_url')
        ->and($post->append('cover_url')->toArray())->toHaveKey('cover_url');
});

test('scopePublished returns only published posts', function () {
    $published = Post::factory()->create([
        'title' => 'Published Post',
        'published_at' => now()->subDay(),
    ]);

    $draft = Post::factory()->draft()->create(['title' => 'Draft Post']);

    $future = Post::factory()->create([
        'title' => 'Future Post',
        'published_at' => now()->addWeek(),
    ]);

    $results = Post::published()->pluck('title')->toArray();

    expect($results)->toContain('Published Post')
        ->and($results)->not->toContain('Draft Post')
        ->and($results)->not->toContain('Future Post');
});

test('scopePublished includes posts published at the current moment', function () {
    $now = now();
    $post = Post::factory()->create(['published_at' => $now]);

    expect(Post::published()->where('id', $post->id)->exists())->toBeTrue();
});

test('scopePublished excludes posts with future published_at dates', function () {
    $future = Post::factory()->create(['published_at' => now()->addSecond()]);

    expect(Post::published()->where('id', $future->id)->exists())->toBeFalse();
});

test('cover_url returns null when no cover image is set', function () {
    $post = Post::factory()->make(['cover_image_path' => null]);

    expect($post->cover_url)->toBeNull();
});

test('cover_url returns null when media disk has no bucket configured', function () {
    config(['filesystems.disks.media.bucket' => null]);
    $post = Post::factory()->make(['cover_image_path' => 'posts/cover.png']);

    expect($post->cover_url)->toBeNull();
});

test('cover_url returns storage url when cover image and media disk are configured', function () {
    Storage::fake('media');
    config(['filesystems.disks.media.bucket' => 'my-bucket']);

    $post = Post::factory()->make(['cover_image_path' => 'posts/cover.png']);

    expect($post->cover_url)->toContain('posts/cover.png');
});

test('bodyHtml converts markdown to html', function () {
    $post = Post::factory()->make([
        'body' => "# Heading\n\nA paragraph with **bold** text.",
    ]);

    expect($post->bodyHtml())->toContain('<h1>Heading</h1>')
        ->and($post->bodyHtml())->toContain('<strong>bold</strong>');
});

test('bodyHtml strips unsafe content', function () {
    $post = Post::factory()->make([
        'body' => '<script>alert(1)</script> [link](javascript:void(0))',
    ]);

    expect($post->bodyHtml())->not->toContain('<script>')
        ->and($post->bodyHtml())->not->toContain('javascript:');
});

test('teaser returns excerpt when set', function () {
    $post = Post::factory()->make([
        'excerpt' => 'A custom teaser.',
        'body' => str_repeat('word ', 50),
    ]);

    expect($post->teaser())->toBe('A custom teaser.');
});

test('teaser falls back to body when excerpt is null', function () {
    $post = Post::factory()->make([
        'excerpt' => null,
        'body' => str_repeat('word ', 50),
    ]);

    $teaser = $post->teaser(10);

    expect($teaser)->toStartWith('word word')
        ->and(str_word_count($teaser))->toBeLessThanOrEqual(11);
});

test('teaser falls back to body when excerpt is empty string', function () {
    $post = Post::factory()->make([
        'excerpt' => '',
        'body' => str_repeat('word ', 50),
    ]);

    $teaser = $post->teaser(10);

    expect($teaser)->toStartWith('word word');
});

test('teaser strips html tags from body', function () {
    $post = Post::factory()->make([
        'excerpt' => null,
        'body' => '# Heading with **bold** and *italic*',
    ]);

    $teaser = $post->teaser();

    expect($teaser)->not->toContain('<h1>')
        ->and($teaser)->not->toContain('<strong>')
        ->and($teaser)->toContain('Heading');
});

test('teaser respects word limit parameter', function () {
    $post = Post::factory()->make([
        'excerpt' => null,
        'body' => str_repeat('word ', 100),
    ]);

    $teaser = $post->teaser(20);

    expect(str_word_count($teaser))->toBeLessThanOrEqual(21);
});

test('factory generates unique slugs to avoid collisions', function () {
    $posts = Post::factory()->count(3)->create();

    $slugs = $posts->pluck('slug')->toArray();

    expect(count($slugs))->toBe(count(array_unique($slugs)));
});

test('factory slug respects explicitly set title', function () {
    $post = Post::factory()->create(['title' => 'My Custom Title Here']);

    expect($post->slug)->toStartWith('my-custom-title-here');
});

test('factory respects explicitly set slug', function () {
    $post = Post::factory()->create(['slug' => 'explicit-slug']);

    expect($post->slug)->toBe('explicit-slug');
});
