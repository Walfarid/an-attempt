<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests are redirected from the post pages', function () {
    $this->get('/dashboard/posts')->assertRedirect('/login');

    $this->post('/dashboard/posts', [])->assertRedirect('/login');
});

test('users see the posts list', function () {
    Post::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/posts')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Posts'));
});

test('users can create a post with an auto-generated slug', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'Why I Keep Coming Back to Laravel!',
        'body' => '## Because it is productive',
        'published_at' => '2026-08-01 09:00:00',
    ])->assertRedirect(route('dashboard.posts.index'));

    $post = Post::where('slug', 'why-i-keep-coming-back-to-laravel')->first();

    expect($post)->not->toBeNull()
        ->and($post?->excerpt)->toBeNull()
        ->and($post?->published_at?->format('Y-m-d'))->toBe('2026-08-01');
});

test('users can set an explicit slug when creating', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'Some Rambling Title',
        'slug' => 'custom-slug',
        'body' => 'Body text.',
    ])->assertRedirect(route('dashboard.posts.index'));

    expect(Post::where('slug', 'custom-slug')->exists())->toBeTrue();
});

test('slugs must be unique', function () {
    Post::factory()->create(['slug' => 'same-slug']);
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'Another post',
        'slug' => 'same-slug',
        'body' => 'Duplicate slug body.',
    ])->assertInvalid('slug');
});

test('users can update a post', function () {
    $post = Post::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/posts/{$post->id}", [
        'title' => 'Updated title',
        'excerpt' => 'A fresh excerpt.',
        'body' => $post->body,
    ])->assertRedirect(route('dashboard.posts.index'));

    $post->refresh();

    expect($post->title)->toBe('Updated title')
        ->and($post->excerpt)->toBe('A fresh excerpt.')
        ->and($post->slug)->toBe($post->slug);
});

test('users can delete a post', function () {
    $post = Post::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/posts/{$post->id}")
        ->assertRedirect(route('dashboard.posts.index'));

    expect(Post::find($post->id))->toBeNull();
});

test('posts require valid data', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'body' => '',
        'published_at' => 'not-a-date',
    ])->assertInvalid(['title', 'body', 'published_at']);
});

test('users can upload a post cover', function () {
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

test('users can remove a post cover', function () {
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

test('deleting a post removes its cover file', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['cover_image_path' => 'posts/cover.png']);
    Storage::disk('media')->put('posts/cover.png', 'bytes');
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/posts/{$post->id}")
        ->assertRedirect(route('dashboard.posts.index'));

    expect(Storage::disk('media')->exists('posts/cover.png'))->toBeFalse();
});
