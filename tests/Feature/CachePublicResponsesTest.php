<?php

use App\Models\Post;
use App\Models\PrivacyPolicy;
use App\Models\Profile;

test('public pages send shared cache headers for guests', function () {
    Profile::factory()->create();
    $post = Post::factory()->create(['published_at' => now()->subDay()]);

    foreach (['/', '/posts', "/posts/{$post->slug}", '/privacy'] as $path) {
        $this->get($path)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=60, public, stale-while-revalidate=300');
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
