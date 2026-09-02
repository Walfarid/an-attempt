<?php

use App\Models\Post;

test('ads.txt 404s while Ezoic ads are disabled', function () {
    config(['services.ezoic.enabled' => false]);

    $this->get('/ads.txt')->assertNotFound();
});

test('ads.txt 301-redirects to the Ezoic adstxt manager when enabled', function () {
    config([
        'services.ezoic.enabled' => true,
        'services.ezoic.adstxt_manager_id' => '19390',
    ]);

    $this->get('/ads.txt')
        ->assertRedirect('https://srv.adstxtmanager.com/19390/'.request()->getHost());
});

test('the single-post page shared ezoic_enabled flag honours config and route', function () {
    $post = Post::factory()->create();

    config(['services.ezoic.enabled' => false]);
    $this->get("/posts/{$post->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('ezoic_enabled', false));

    config(['services.ezoic.enabled' => true]);
    $this->get("/posts/{$post->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('ezoic_enabled', true));
});

test('the ezoic_enabled flag is false off the single-post page', function () {
    Post::factory()->create();

    config(['services.ezoic.enabled' => true]);

    $this->get('/posts')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('ezoic_enabled', false));
});
