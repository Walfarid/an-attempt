<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

test('guests cannot tag posts', function () {
    $post = Post::factory()->create();

    $this->put("/dashboard/posts/{$post->id}", [
        'title' => $post->title,
        'body' => $post->body,
        'tags' => ['laravel'],
    ])->assertRedirect('/login');
});

test('users can store a post with tags', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'Tagged post',
        'body' => 'Body.',
        'tags' => ['Laravel', 'PHP'],
    ])->assertRedirect(route('dashboard.posts.index'));

    $post = Post::where('slug', 'tagged-post')->firstOrFail();

    expect($post->tags->pluck('name')->all())->toBe(['Laravel', 'PHP']);
});

test('tags are created on first use and reused afterwards', function () {
    $existing = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

    $this->actingAs(User::factory()->create());

    $post = Post::factory()->create();

    $this->put("/dashboard/posts/{$post->id}", [
        'title' => $post->title,
        'body' => $post->body,
        'tags' => ['Laravel', 'Testing'],
    ])->assertRedirect(route('dashboard.posts.index'));

    expect($post->fresh()->tags->pluck('name')->sort()->values()->all())
        ->toBe(['Laravel', 'Testing'])
        ->and(Tag::where('name', 'Laravel')->value('id'))->toBe($existing->id)
        ->and(Tag::where('name', 'Testing')->value('slug'))->toBe('testing');
});

test('updating a post replaces its tag set and orphans the dropped tag', function () {
    $post = Post::factory()->create();
    $post->tags()->attach(Tag::factory()->create(['name' => 'Old', 'slug' => 'old']));

    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/posts/{$post->id}", [
        'title' => $post->title,
        'body' => $post->body,
        'tags' => ['New'],
    ])->assertRedirect(route('dashboard.posts.index'));

    $post->refresh();

    expect($post->tags->pluck('name')->all())->toBe(['New']);
});

test('submitting an empty tag list detaches everything', function () {
    $post = Post::factory()->create();
    $post->tags()->attach(Tag::factory()->create(['name' => 'Gone', 'slug' => 'gone']));

    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/posts/{$post->id}", [
        'title' => $post->title,
        'body' => $post->body,
        'tags' => [],
    ])->assertRedirect(route('dashboard.posts.index'));

    expect($post->fresh()->tags)->toBeEmpty();
});

test('tags are normalized: trimmed, deduped case-insensitively', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'Normalization',
        'body' => 'Body.',
        'tags' => ['  Laravel  ', 'laravel', 'PHP'],
    ])->assertRedirect(route('dashboard.posts.index'));

    $post = Post::where('slug', 'normalization')->firstOrFail();

    expect($post->tags->pluck('name')->all())->toBe(['Laravel', 'PHP']);
});

test('more than ten tags is rejected', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'Too many',
        'body' => 'Body.',
        'tags' => array_map(fn ($i) => "tag-{$i}", range(1, 11)),
    ])->assertInvalid('tags');
});

test('individual tag names are length-limited', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'Long tag',
        'body' => 'Body.',
        'tags' => [str_repeat('x', 31)],
    ])->assertInvalid('tags.0');
});

test('tags with identical slugs get distinct suffixed slugs', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'First post',
        'body' => 'Body.',
        'tags' => ['Foo Bar'],
    ])->assertRedirect(route('dashboard.posts.index'));

    $this->post('/dashboard/posts', [
        'title' => 'Second post',
        'body' => 'Body.',
        'tags' => ['foo-bar'],
    ])->assertRedirect(route('dashboard.posts.index'));

    $tag1 = Tag::where('name', 'Foo Bar')->firstOrFail();
    $tag2 = Tag::where('name', 'foo-bar')->firstOrFail();

    expect($tag1->slug)->toBe('foo-bar')
        ->and($tag2->slug)->toBe('foo-bar-1')
        ->and($tag1->id)->not->toBe($tag2->id);

    $post1 = Post::where('slug', 'first-post')->firstOrFail();
    $post2 = Post::where('slug', 'second-post')->firstOrFail();

    expect($post1->tags->pluck('name')->all())->toBe(['Foo Bar'])
        ->and($post2->tags->pluck('name')->all())->toBe(['foo-bar']);
});

test('creating a post with several new tags creates all of them at once', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'Multi tag',
        'body' => 'Body.',
        'tags' => ['Alpha', 'Beta', 'Gamma', 'Delta'],
    ])->assertRedirect(route('dashboard.posts.index'));

    $post = Post::where('slug', 'multi-tag')->firstOrFail();

    expect($post->tags)->toHaveCount(4)
        ->and(Tag::whereIn('name', ['Alpha', 'Beta', 'Gamma', 'Delta'])->count())->toBe(4);
});

test('tags with colliding slugs in the same batch get distinct slugs', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/posts', [
        'title' => 'Collision batch',
        'body' => 'Body.',
        'tags' => ['Hello World', 'hello-world'],
    ])->assertRedirect(route('dashboard.posts.index'));

    $tag1 = Tag::where('name', 'Hello World')->firstOrFail();
    $tag2 = Tag::where('name', 'hello-world')->firstOrFail();

    expect($tag1->slug)->not->toBe($tag2->slug)
        ->and($tag1->slug)->toBe('hello-world')
        ->and($tag2->slug)->toBe('hello-world-1');
});

test('deleting a post detaches its tags but keeps shared tags', function () {
    $post = Post::factory()->create();
    $other = Post::factory()->create();
    $tag = Tag::factory()->create(['name' => 'Shared', 'slug' => 'shared']);

    $post->tags()->attach($tag);
    $other->tags()->attach($tag);

    $this->actingAs(User::factory()->create());
    $this->delete("/dashboard/posts/{$post->id}")
        ->assertRedirect(route('dashboard.posts.index'));

    expect(Tag::find($tag->id))->not->toBeNull()
        ->and($other->fresh()->tags->pluck('name')->all())->toBe(['Shared']);
});
