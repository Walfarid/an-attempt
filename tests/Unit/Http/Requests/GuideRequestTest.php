<?php

use App\Http\Requests\Dashboard\GuideRequest;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('rules returns the expected validation structure', function () {
    $request = new GuideRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['title', 'slug', 'body', 'teaser', 'prerequisites', 'estimated_time', 'published_at', 'posts'])
        ->and($rules['title'])->toContain('required', 'string', 'max:255')
        ->and($rules['slug'])->toContain('required', 'string', 'max:255', 'alpha_dash')
        ->and($rules['body'])->toContain('required', 'string')
        ->and($rules['teaser'])->toContain('nullable', 'string', 'max:300')
        ->and($rules['prerequisites'])->toContain('nullable', 'string')
        ->and($rules['estimated_time'])->toContain('nullable', 'string', 'max:100')
        ->and($rules['published_at'])->toContain('nullable', 'date')
        ->and($rules['posts'])->toContain('nullable', 'array')
        ->and($rules['posts.*'])->toContain('integer', 'exists:posts,id');
});

test('slug must be unique when creating a guide', function () {
    Guide::factory()->create(['slug' => 'existing-slug']);
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/guides', [
        'title' => 'New Guide',
        'slug' => 'existing-slug',
        'body' => 'Content',
    ])->assertInvalid('slug');
});

test('slug can remain the same when updating a guide', function () {
    $guide = Guide::factory()->create(['slug' => 'keep-this-slug']);
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/guides/{$guide->id}", [
        'title' => 'Updated',
        'slug' => 'keep-this-slug',
        'body' => 'Updated content',
    ])->assertValid('slug');
});

test('slug cannot be changed to another guides slug', function () {
    Guide::factory()->create(['slug' => 'other-guide-slug']);
    $guide = Guide::factory()->create(['slug' => 'unique-guide-slug']);
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/guides/{$guide->id}", [
        'title' => 'Updated',
        'slug' => 'other-guide-slug',
        'body' => 'Updated content',
    ])->assertInvalid('slug');
});

test('a valid guide payload passes validation', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/guides', [
        'title' => 'A different guide',
        'slug' => 'a-different-guide',
        'body' => 'Guide body',
        'teaser' => 'A teaser',
        'published_at' => '2026-08-01 09:00:00',
    ])->assertValid();
});

test('request uses default authorization (no authorize method defined)', function () {
    expect(method_exists(new GuideRequest, 'authorize'))->toBeFalse();
});
