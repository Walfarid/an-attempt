<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('markdown bodies are rendered to html', function () {
    $post = Post::factory()->make([
        'body' => "# Heading\n\nA **bold** claim.",
    ]);

    expect($post->bodyHtml())->toContain('<h1>Heading</h1>')
        ->and($post->bodyHtml())->toContain('<strong>bold</strong>');
});

test('unsafe markdown input is stripped', function () {
    $post = Post::factory()->make([
        'body' => '<script>alert(1)</script> [x](javascript:alert(2))',
    ]);

    expect($post->bodyHtml())->not->toContain('<script>')
        ->and($post->bodyHtml())->not->toContain('javascript:');
});

test('the teaser prefers the excerpt and falls back to the body', function () {
    $withExcerpt = Post::factory()->make([
        'excerpt' => 'Hand-written teaser.',
        'body' => str_repeat('word ', 60),
    ]);

    $withoutExcerpt = Post::factory()->make([
        'excerpt' => null,
        'body' => str_repeat('word ', 60).'end',
    ]);

    expect($withExcerpt->teaser())->toBe('Hand-written teaser.')
        ->and($withoutExcerpt->teaser(10))->toStartWith('word word word')
        ->and(str_word_count($withoutExcerpt->teaser(10)))->toBeLessThanOrEqual(11);
});

test('guests cannot manage covers', function () {
    $post = Post::factory()->create();

    $this->put("/dashboard/posts/{$post->id}/cover", [])
        ->assertRedirect('/login');
});

test('users can upload a cover image', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['cover_image_path' => null]);
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/posts/{$post->id}/cover", [
        'cover' => UploadedFile::fake()->image('cover.png'),
    ])->assertRedirect(route('dashboard.posts.index'));

    $post->refresh();

    expect($post->cover_image_path)->not->toBeNull()
        ->and(Storage::disk('media')->exists((string) $post->cover_image_path))->toBeTrue();
});

test('uploading a new cover replaces the old file', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['cover_image_path' => 'posts/old-cover.png']);
    Storage::disk('media')->put('posts/old-cover.png', 'old-bytes');
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/posts/{$post->id}/cover", [
        'cover' => UploadedFile::fake()->image('new-cover.png'),
    ])->assertRedirect(route('dashboard.posts.index'));

    $post->refresh();

    expect(Storage::disk('media')->exists('posts/old-cover.png'))->toBeFalse()
        ->and(Storage::disk('media')->exists((string) $post->cover_image_path))->toBeTrue()
        ->and($post->cover_image_path)->not->toBe('posts/old-cover.png');
});

test('users can remove a cover image', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['cover_image_path' => 'posts/cover-to-remove.png']);
    Storage::disk('media')->put('posts/cover-to-remove.png', 'bytes');
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/posts/{$post->id}/cover")
        ->assertRedirect(route('dashboard.posts.index'));

    $post->refresh();

    expect($post->cover_image_path)->toBeNull()
        ->and(Storage::disk('media')->exists('posts/cover-to-remove.png'))->toBeFalse();
});

test('covers must be images', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['cover_image_path' => null]);
    $this->actingAs(User::factory()->create());

    $this->from('/dashboard/posts')->put("/dashboard/posts/{$post->id}/cover", [
        'cover' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
    ])->assertInvalid('cover');

    expect($post->fresh()->cover_image_path)->toBeNull();
});
