<?php

use App\Models\Post;

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
