<?php

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('factory creates a valid tag', function () {
    $tag = Tag::factory()->create();

    expect($tag)->toBeInstanceOf(Tag::class)
        ->and($tag->name)->toBeString()
        ->and($tag->slug)->toBeString();
});

test('fillable attributes are mass assignable', function () {
    $tag = Tag::create([
        'name' => 'Laravel',
        'slug' => 'laravel',
    ]);

    expect($tag->name)->toBe('Laravel')
        ->and($tag->slug)->toBe('laravel');
});

test('id and timestamps are not mass assignable', function () {
    $tag = Tag::create([
        'name' => 'Protected',
        'slug' => 'protected',
        'id' => 999,
    ]);

    expect($tag->id)->not->toBe(999);
});

test('scopeUsed returns tags attached to published posts', function () {
    $published = Post::factory()->create(['published_at' => now()->subDay()]);
    $usedTag = Tag::create(['name' => 'Used Tag', 'slug' => 'used-tag']);
    $unusedTag = Tag::create(['name' => 'Unused Tag', 'slug' => 'unused-tag']);

    $published->tags()->attach($usedTag);

    $results = Tag::used()->pluck('id')->toArray();

    expect($results)->toContain($usedTag->id)
        ->and($results)->not->toContain($unusedTag->id);
});

test('scopeUsed excludes tags only attached to draft posts', function () {
    $draft = Post::factory()->draft()->create();
    $tag = Tag::create(['name' => 'Draft Only', 'slug' => 'draft-only']);

    $draft->tags()->attach($tag);

    expect(Tag::used()->pluck('id')->toArray())->not->toContain($tag->id);
});

test('scopeUsed excludes tags only attached to future posts', function () {
    $future = Post::factory()->create(['published_at' => now()->addWeek()]);
    $tag = Tag::create(['name' => 'Future Only', 'slug' => 'future-only']);

    $future->tags()->attach($tag);

    expect(Tag::used()->pluck('id')->toArray())->not->toContain($tag->id);
});

test('scopeUsed returns empty collection when no tags exist', function () {
    expect(Tag::used()->get())->toBeEmpty();
});

test('posts relationship is a belongsToMany', function () {
    $tag = new Tag;

    expect($tag->posts())->toBeInstanceOf(BelongsToMany::class);
});

test('timestamps are automatically set on creation', function () {
    $tag = Tag::factory()->create();

    expect($tag->created_at)->not->toBeNull()
        ->and($tag->updated_at)->not->toBeNull();
});
