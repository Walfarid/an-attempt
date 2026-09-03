<?php

use App\Models\Guide;
use App\Models\Post;
use App\Models\PrivacyPolicy;
use App\Models\Profile;
use App\Models\Tag;

test('public pages send shared cache headers for guests', function () {
    Profile::factory()->create();
    $post = Post::factory()->create(['published_at' => now()->subDay()]);

    foreach (['/', '/posts', "/posts/{$post->slug}", '/privacy'] as $path) {
        $this->get($path)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=60, must-revalidate, public, stale-while-revalidate=300');
    }
});

test('the blog show exposes the post update time as Last-Modified', function () {
    $post = Post::factory()->create(['published_at' => now()->subDay()]);

    $this->get("/posts/{$post->slug}")
        ->assertOk()
        ->assertHeader('Last-Modified', $post->updated_at->toRfc7231String());
});

test('blog show returns 304 when the cached copy is fresh', function () {
    $post = Post::factory()->create(['published_at' => now()->subDay()]);

    // A header matching the post's updated_at is still fresh...
    $this->get("/posts/{$post->slug}", [
        'If-Modified-Since' => $post->updated_at->toRfc7231String(),
    ])->assertStatus(304);

    // ...and so is one newer than it.
    $this->get("/posts/{$post->slug}", [
        'If-Modified-Since' => $post->updated_at->addSecond()->toRfc7231String(),
    ])->assertStatus(304);
});

test('blog show serves a fresh 200 when the cached copy is stale', function () {
    $post = Post::factory()->create(['published_at' => now()->subDay()]);

    $this->get("/posts/{$post->slug}", [
        'If-Modified-Since' => $post->updated_at->subSecond()->toRfc7231String(),
    ])
        ->assertOk()
        ->assertHeader('Last-Modified', $post->updated_at->toRfc7231String());
});

test('the privacy page exposes the policy update time as Last-Modified', function () {
    $policy = PrivacyPolicy::factory()->create();

    $this->get('/privacy')
        ->assertOk()
        ->assertHeader('Last-Modified', $policy->updated_at->toRfc7231String());
});

test('blog index exposes the newest post update time as Last-Modified and returns 304 when fresh', function () {
    $post = Post::factory()->create(['published_at' => now()->subDay()]);

    $response = $this->get('/posts')
        ->assertOk()
        ->assertHeader('Last-Modified', $post->updated_at->toRfc7231String());
    $lastModified = $response->headers->get('Last-Modified');

    $this->get('/posts', ['If-Modified-Since' => $lastModified])->assertStatus(304);
    $this->get('/posts', ['If-Modified-Since' => $post->updated_at->addSecond()->toRfc7231String()])->assertStatus(304);
    $this->get('/posts', ['If-Modified-Since' => $post->updated_at->subSecond()->toRfc7231String()])->assertOk();
});

test('blog tag page exposes the newest tagged post update time and returns 304 when fresh', function () {
    $tag = Tag::factory()->create();
    /** @var Post $post */
    $post = Post::factory()->create(['published_at' => now()->subDay()]);
    $post->tags()->attach($tag);

    $response = $this->get("/posts/tag/{$tag->slug}")
        ->assertOk()
        ->assertHeader('Last-Modified', $post->updated_at->toRfc7231String());
    $lastModified = $response->headers->get('Last-Modified');

    $this->get("/posts/tag/{$tag->slug}", ['If-Modified-Since' => $lastModified])->assertStatus(304);
    $this->get("/posts/tag/{$tag->slug}", ['If-Modified-Since' => $post->updated_at->addSecond()->toRfc7231String()])->assertStatus(304);
    $this->get("/posts/tag/{$tag->slug}", ['If-Modified-Since' => $post->updated_at->subSecond()->toRfc7231String()])->assertOk();
});

test('guides index exposes the newest guide update time and returns 304 when fresh', function () {
    $guide = Guide::factory()->create(['published_at' => now()->subDay()]);

    $response = $this->get('/guides')
        ->assertOk()
        ->assertHeader('Last-Modified', $guide->updated_at->toRfc7231String());
    $lastModified = $response->headers->get('Last-Modified');

    $this->get('/guides', ['If-Modified-Since' => $lastModified])->assertStatus(304);
    $this->get('/guides', ['If-Modified-Since' => $guide->updated_at->addSecond()->toRfc7231String()])->assertStatus(304);
    $this->get('/guides', ['If-Modified-Since' => $guide->updated_at->subSecond()->toRfc7231String()])->assertOk();
});

test('privacy page returns 304 when the cached copy is fresh', function () {
    $policy = PrivacyPolicy::factory()->create();

    // A header matching the policy's updated_at is still fresh...
    $this->get('/privacy', [
        'If-Modified-Since' => $policy->updated_at->toRfc7231String(),
    ])->assertStatus(304);

    // ...and so is one newer than it.
    $this->get('/privacy', [
        'If-Modified-Since' => $policy->updated_at->addSecond()->toRfc7231String(),
    ])->assertStatus(304);
});

test('privacy page serves a fresh 200 when the cached copy is stale', function () {
    $policy = PrivacyPolicy::factory()->create();

    $this->get('/privacy', [
        'If-Modified-Since' => $policy->updated_at->subSecond()->toRfc7231String(),
    ])
        ->assertOk()
        ->assertHeader('Last-Modified', $policy->updated_at->toRfc7231String());
});
