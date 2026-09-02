<?php

use App\Models\Guide;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests are redirected from the guide pages', function () {
    $this->get('/dashboard/guides')->assertRedirect('/login');

    $this->post('/dashboard/guides', [])->assertRedirect('/login');
});

test('authenticated users see the guides list', function () {
    Guide::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/guides')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Guides'));
});

test('users can create a guide', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/guides', [
        'title' => 'Getting Started with Docker',
        'body' => '## Step 1: Install Docker',
        'prerequisites' => 'A Linux server with sudo.',
        'estimated_time' => '30 minutes',
    ])->assertRedirect(route('dashboard.guides.index'));

    $guide = Guide::where('slug', 'getting-started-with-docker')->first();

    expect($guide)->not->toBeNull()
        ->and($guide?->prerequisites)->toBe('A Linux server with sudo.')
        ->and($guide?->estimated_time)->toBe('30 minutes');
});

test('users can update a guide', function () {
    $guide = Guide::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/guides/{$guide->id}", [
        'title' => 'Updated guide title',
        'body' => $guide->body,
        'estimated_time' => '1 hour',
    ])->assertRedirect(route('dashboard.guides.index'));

    $guide->refresh();

    expect($guide->title)->toBe('Updated guide title')
        ->and($guide->estimated_time)->toBe('1 hour')
        ->and($guide->slug)->toBe($guide->slug);
});

test('users can delete a guide', function () {
    $guide = Guide::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/guides/{$guide->id}")
        ->assertRedirect(route('dashboard.guides.index'));

    expect(Guide::find($guide->id))->toBeNull();
});

test('guides require a title', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/guides', [
        'body' => 'Content',
    ])->assertInvalid('title');
});

test('guides require a body', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/guides', [
        'title' => 'A guide',
    ])->assertInvalid('body');
});

test('guide slugs must be unique', function () {
    Guide::factory()->create(['slug' => 'same-slug']);
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/guides', [
        'title' => 'Another guide',
        'slug' => 'same-slug',
        'body' => 'Duplicate slug body.',
    ])->assertInvalid('slug');
});

test('users can sync related posts when creating a guide', function () {
    $posts = Post::factory()->count(2)->create();
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/guides', [
        'title' => 'Guide with posts',
        'body' => 'Body.',
        'posts' => $posts->pluck('id')->all(),
    ])->assertRedirect(route('dashboard.guides.index'));

    $guide = Guide::where('slug', 'guide-with-posts')->first();

    expect($guide)->not->toBeNull()
        ->and($guide->posts()->pluck('posts.id')->toArray())->toEqualCanonicalizing($posts->pluck('id')->all());
});

test('users can upload a guide cover', function () {
    Storage::fake('media');
    $guide = Guide::factory()->create(['cover_image_path' => null]);
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/guides/{$guide->id}/cover", [
        'cover' => UploadedFile::fake()->image('cover.png'),
    ])->assertRedirect(route('dashboard.guides.index'));

    $guide->refresh();

    expect($guide->cover_image_path)->not->toBeNull()
        ->and(Storage::disk('media')->exists((string) $guide->cover_image_path))->toBeTrue();
});

test('users can remove a guide cover', function () {
    Storage::fake('media');
    $guide = Guide::factory()->create(['cover_image_path' => 'guides/cover-to-remove.png']);
    Storage::disk('media')->put('guides/cover-to-remove.png', 'bytes');
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/guides/{$guide->id}/cover")
        ->assertRedirect(route('dashboard.guides.index'));

    $guide->refresh();

    expect($guide->cover_image_path)->toBeNull()
        ->and(Storage::disk('media')->exists('guides/cover-to-remove.png'))->toBeFalse();
});

test('deleting a guide removes its cover file', function () {
    Storage::fake('media');
    $guide = Guide::factory()->create(['cover_image_path' => 'guides/cover.png']);
    Storage::disk('media')->put('guides/cover.png', 'bytes');
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/guides/{$guide->id}")
        ->assertRedirect(route('dashboard.guides.index'));

    expect(Storage::disk('media')->exists('guides/cover.png'))->toBeFalse();
});
