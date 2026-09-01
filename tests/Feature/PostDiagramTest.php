<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests cannot manage diagrams', function () {
    $post = Post::factory()->create();

    $this->put("/dashboard/posts/{$post->id}/diagram", [])
        ->assertRedirect('/login');
});

test('users can upload a diagram', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['diagram_path' => null]);
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/posts/{$post->id}/diagram", [
        'diagram' => UploadedFile::fake()->create('diagram.html', 100, 'text/html'),
    ])->assertRedirect(route('dashboard.posts.index'));

    $post->refresh();

    expect($post->diagram_path)->not->toBeNull()
        ->and(Storage::disk('media')->exists((string) $post->diagram_path))->toBeTrue();
});

test('uploading a new diagram replaces the old file', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['diagram_path' => 'posts/old-diagram.html']);
    Storage::disk('media')->put('posts/old-diagram.html', 'old-bytes');
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/posts/{$post->id}/diagram", [
        'diagram' => UploadedFile::fake()->create('new-diagram.html', 100, 'text/html'),
    ])->assertRedirect(route('dashboard.posts.index'));

    $post->refresh();

    expect(Storage::disk('media')->exists('posts/old-diagram.html'))->toBeFalse()
        ->and(Storage::disk('media')->exists((string) $post->diagram_path))->toBeTrue()
        ->and($post->diagram_path)->not->toBe('posts/old-diagram.html');
});

test('users can remove a diagram', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['diagram_path' => 'posts/diagram-to-remove.html']);
    Storage::disk('media')->put('posts/diagram-to-remove.html', 'bytes');
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/posts/{$post->id}/diagram")
        ->assertRedirect(route('dashboard.posts.index'));

    $post->refresh();

    expect($post->diagram_path)->toBeNull()
        ->and(Storage::disk('media')->exists('posts/diagram-to-remove.html'))->toBeFalse();
});

test('diagrams must be html files', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['diagram_path' => null]);
    $this->actingAs(User::factory()->create());

    $this->from('/dashboard/posts')->put("/dashboard/posts/{$post->id}/diagram", [
        'diagram' => UploadedFile::fake()->create('diagram.pdf', 100, 'application/pdf'),
    ])->assertInvalid('diagram');

    expect($post->fresh()->diagram_path)->toBeNull();
});

test('deleting a post removes its diagram file', function () {
    Storage::fake('media');
    $post = Post::factory()->create(['diagram_path' => 'posts/doomed-diagram.html']);
    Storage::disk('media')->put('posts/doomed-diagram.html', 'bytes');
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/posts/{$post->id}");

    expect(Storage::disk('media')->exists('posts/doomed-diagram.html'))->toBeFalse();
});

test('the public diagram route streams the attached html', function () {
    Storage::fake('media');
    $post = Post::factory()->create([
        'slug' => 'richardson-maturity',
        'diagram_path' => 'posts/diagram.html',
    ]);
    Storage::disk('media')->put('posts/diagram.html', '<!doctype html><svg></svg>');

    $this->get('/diagrams/richardson-maturity')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=utf-8')
        ->assertSee('<!doctype html>', false);
});

test('the public diagram route 404s without an attached diagram', function () {
    Post::factory()->create(['slug' => 'no-diagram', 'diagram_path' => null]);

    $this->get('/diagrams/no-diagram')->assertNotFound();
});

test('the public diagram route 404s for unknown slugs', function () {
    $this->get('/diagrams/never-existed')->assertNotFound();
});
