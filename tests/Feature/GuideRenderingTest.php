<?php

use App\Models\Guide;
use App\Models\Post;

test('guides index shows published guides newest first', function () {
    $older = Guide::factory()->create([
        'title' => 'An older guide',
        'published_at' => now()->subDays(2),
    ]);
    $newer = Guide::factory()->create([
        'title' => 'A newer guide',
        'published_at' => now()->subDay(),
    ]);
    Guide::factory()->draft()->create(['title' => 'A draft guide']);

    $this->get('/guides')
        ->assertOk()
        ->assertInertia(function ($page) use ($older, $newer) {
            $props = $page->toArray()['props'];
            $slugs = array_column($props['guides']['data'], 'slug');

            expect($slugs)->toHaveCount(2)
                ->and($slugs)->toContain($newer->slug, $older->slug)
                ->and($slugs[0])->toBe($newer->slug);
        });
});

test('guides index excludes drafts', function () {
    Guide::factory()->draft()->create(['title' => 'A draft guide']);

    $this->get('/guides')
        ->assertOk()
        ->assertInertia(function ($page) {
            $props = $page->toArray()['props'];

            expect($props['guides']['data'])->toBeEmpty();
        });
});

test('guide show renders a published guide', function () {
    $guide = Guide::factory()->create();

    $this->get("/guides/{$guide->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guides/Show')
            ->where('guide.title', $guide->title));
});

test('guide show returns 404 for draft', function () {
    $guide = Guide::factory()->draft()->create();

    $this->get("/guides/{$guide->slug}")->assertNotFound();
});

test('guide show returns 404 for future guide', function () {
    $guide = Guide::factory()->create(['published_at' => now()->addWeek()]);

    $this->get("/guides/{$guide->slug}")->assertNotFound();
});

test('guide show renders body as html', function () {
    $guide = Guide::factory()->make(['body' => '# Heading']);
    $guide->save();

    $this->get("/guides/{$guide->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guides/Show')
            ->where('guide.body_html', "<h1>Heading</h1>\n"));
});

test('guide show includes related posts', function () {
    $post = Post::factory()->create(['title' => 'The underlying guide post']);
    $guide = Guide::factory()->create();

    $guide->posts()->sync([$post->id]);

    $this->get("/guides/{$guide->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guides/Show')
            ->where('guide.posts.0.title', $post->title));
});

test('guide show includes prerequisites when set', function () {
    $guide = Guide::factory()->create(['prerequisites' => 'PHP, Composer, and a server']);

    $this->get("/guides/{$guide->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guides/Show')
            ->where('guide.prerequisites', 'PHP, Composer, and a server'));
});

test('guide show includes estimated time when set', function () {
    $guide = Guide::factory()->create(['estimated_time' => '45 minutes']);

    $this->get("/guides/{$guide->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guides/Show')
            ->where('guide.estimated_time', '45 minutes'));
});
