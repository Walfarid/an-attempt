<?php

use App\Models\Post;
use App\Models\Tag;

test('a published post ships its tags in the payload', function () {
    $post = Post::factory()->create();
    $post->tags()->attach(Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']));

    $this->get(route('posts.show', $post->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('posts/Show')
            ->where('post.tags.0.name', 'Laravel')
            ->where('post.tags.0.slug', 'laravel'));
});

test('a draft post still 404s', function () {
    $post = Post::factory()->draft()->create();

    $this->get('/posts/anything')->assertNotFound();
});

test('the tag page lists only that tag and only published posts', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    $other = Tag::factory()->create(['name' => 'Other', 'slug' => 'other']);

    $tagged = Post::factory()->create(['title' => 'Tagged article']);
    $draftTagged = Post::factory()->draft()->create(['title' => 'Draft article']);
    $untagged = Post::factory()->create(['title' => 'Untagged article']);

    $tag->posts()->attach([$tagged->id, $draftTagged->id]);
    $other->posts()->attach($untagged->id);

    $this->get(route('posts.tag', $tag->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('posts/Tag')
            ->where('tag.name', 'Laravel')
            ->where('tag.slug', 'laravel')
            ->has('posts', 1)
            ->where('posts.0.title', 'Tagged article'));
});

test('the tag page carries its own meta tags', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

    $html = $this->get(route('posts.tag', $tag->slug))->getContent();

    expect($html)
        ->toContain('Posts tagged')
        ->toContain('rel="canonical"')
        ->toContain('/posts/tag/laravel');
});

test('unknown tag slugs 404', function () {
    $this->get('/posts/tag/does-not-exist')->assertNotFound();
});

test('blog index shows tags on cards', function () {
    $post = Post::factory()->create();
    $post->tags()->attach(Tag::factory()->create(['name' => 'Docker', 'slug' => 'docker']));

    $this->get('/posts')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('posts/Index')
            ->where('posts.data.0.tags.0.name', 'Docker'));
});

test('post pages ship BlogPosting JSON-LD with keywords', function () {
    $post = Post::factory()->create(['excerpt' => 'A unique excerpt.']);
    $post->tags()->attach(Tag::factory()->create(['name' => 'Testing', 'slug' => 'testing']));

    $html = $this->get(route('posts.show', $post->slug))->getContent();

    expect($html)
        ->toContain('application/ld+json')
        ->toContain('"@type":"BlogPosting"')
        ->toContain('"headline":"'.$post->title.'"')
        ->toContain('"keywords"')
        ->toContain('Testing');
});

test('post pages fall back to the default og image when they have no cover', function () {
    $post = Post::factory()->create(['cover_image_path' => null]);

    $html = $this->get(route('posts.show', $post->slug))->getContent();

    expect($html)->toContain('property="og:image"')
        ->toContain('og-default.png');
});
