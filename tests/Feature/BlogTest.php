<?php

use App\Models\Post;

test('the blog index lists published posts newest first', function () {
    $older = Post::factory()->create([
        'title' => 'An older note',
        'published_at' => now()->subDays(2),
    ]);
    $newer = Post::factory()->create([
        'title' => 'A newer note',
        'published_at' => now()->subDay(),
    ]);
    Post::factory()->draft()->create(['title' => 'A draft note']);

    $this->get('/posts')
        ->assertOk()
        ->assertInertia(function ($page) use ($older, $newer) {
            $props = $page->toArray()['props'];
            $slugs = array_column($props['posts']['data'], 'slug');

            expect($slugs)->toContain($newer->slug, $older->slug)
                ->and($slugs[0])->toBe($newer->slug);
        });
});

test('a published post renders its markdown body', function () {
    $post = Post::factory()->make(['body' => '# Heading']);
    $post->save();

    $this->get("/posts/{$post->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('posts/Show')
            ->where('post.title', $post->title)
            ->where('post.body_html', "<h1>Heading</h1>\n"));
});

test('draft posts are not found on the public site', function () {
    $post = Post::factory()->draft()->create();

    $this->get("/posts/{$post->slug}")->assertNotFound();
});

test('the post page includes recent published posts', function () {
    Post::factory()->create([
        'title' => 'An older note',
        'published_at' => now()->subDays(2),
    ]);
    $newer = Post::factory()->create([
        'title' => 'A newer note',
        'published_at' => now()->subDay(),
    ]);

    // `recent` is inlined into the post query as JSON — keep the shape covered.
    $this->get("/posts/{$newer->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('posts/Show')
            ->where('recent.0.title', 'An older note')
            ->where('recent.0.slug', Post::query()->where('title', 'An older note')->value('slug')));
});

test('future-dated posts are not found until their publish time', function () {
    $post = Post::factory()->create(['published_at' => now()->addWeek()]);

    $this->get("/posts/{$post->slug}")->assertNotFound();
});
