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
