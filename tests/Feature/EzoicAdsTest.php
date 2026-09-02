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

test('the post page shares the placement id only while Ezoic ads are enabled', function () {
    $post = Post::factory()->create();

    config([
        'services.ezoic.enabled' => true,
        'services.ezoic.placeholder_id' => 148,
    ]);

    $this->get("/posts/{$post->slug}")
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('ezoic_enabled', true)
                ->where('ezoic_placeholder_id', 148),
        );

    config(['services.ezoic.enabled' => false]);

    $this->get("/posts/{$post->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('ezoic_placeholder_id', null));
});

test('the Ezoic standalone scripts load on the post page only after consent', function () {
    $post = Post::factory()->create();

    config(['services.ezoic.enabled' => true]);

    $this->get("/posts/{$post->slug}")
        ->assertOk()
        ->assertDontSee('ezojs.com');

    // Plain cookie, like the banner's document.cookie write in the browser —
    // 'consent' is exempt from encryption, so withCookie would double-wrap it.
    $this->withUnencryptedCookie('consent', 'accepted')
        ->get("/posts/{$post->slug}")
        ->assertOk()
        ->assertSee('ezojs.com');

    $this->withUnencryptedCookie('consent', 'accepted')
        ->get('/posts')
        ->assertOk()
        ->assertDontSee('ezojs.com');
});
