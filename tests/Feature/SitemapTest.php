<?php

use App\Models\Guide;
use App\Models\Post;
use App\Models\PrivacyPolicy;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

test('sitemap returns xml content', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml');
});

test('sitemap contains homepage and blog index', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertSee(url('/'))
        ->assertSee(url('/posts'));
});

test('sitemap includes published posts', function () {
    $post = Post::factory()->create(['title' => 'Sitemap Test Post']);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertSee(route('posts.show', $post->slug));
});

test('sitemap excludes unpublished posts', function () {
    $post = Post::factory()->create(['published_at' => null]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertDontSee(route('posts.show', $post->slug));
});

test('sitemap includes privacy page', function () {
    $privacy = PrivacyPolicy::factory()->create(['body' => 'Sample privacy body.']);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertSee(url('/privacy'))
        ->assertSee($privacy->updated_at->toW3cString());
});

test('sitemap includes tags used by published posts', function () {
    $tag = Tag::factory()->create(['slug' => 'laravel']);
    $post = Post::factory()->create();
    $post->tags()->attach($tag);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(url('/posts/tag/laravel'));
});

test('sitemap excludes tags without published posts', function () {
    $tag = Tag::factory()->create(['slug' => 'lonely']);
    $post = Post::factory()->draft()->create();
    $post->tags()->attach($tag);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertDontSee('/posts/tag/lonely');
});

test('sitemap includes guides index', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(url('/guides'));
});

test('sitemap includes published guides', function () {
    $guide = Guide::factory()->create(['title' => 'Sitemap Test Guide']);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(route('guides.show', $guide->slug));
});

test('sitemap excludes draft guides', function () {
    $guide = Guide::factory()->create(['published_at' => null]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertDontSee(route('guides.show', $guide->slug));
});

test('sitemap returns 304 on a conditional request with a fresh If-Modified-Since', function () {
    Post::factory()->create();

    $response = $this->get('/sitemap.xml');
    $response->assertOk();

    $lastModified = $response->headers->get('Last-Modified');

    expect($lastModified)->not->toBeNull();

    $this->withHeader('If-Modified-Since', $lastModified)
        ->get('/sitemap.xml')
        ->assertStatus(304);
});

test('sitemap reflects new posts after cache invalidation', function () {
    Post::factory()->create();

    $this->get('/sitemap.xml')->assertOk();

    // Simulate what the dashboard PostController does after a save.
    Cache::forget('sitemap.xml');
    Cache::forget('sitemap.last_modified');

    $newPost = Post::factory()->create(['title' => 'Freshly Published']);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(route('posts.show', $newPost->slug));
});
