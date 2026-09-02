<?php

use App\Models\PrivacyPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2025-08-26 12:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('factory creates a valid privacy policy', function () {
    $policy = PrivacyPolicy::factory()->create();

    expect($policy)->toBeInstanceOf(PrivacyPolicy::class)
        ->and($policy->body)->toBeString()
        ->and($policy->created_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('fillable attributes are mass assignable', function () {
    $policy = PrivacyPolicy::create(['body' => 'Custom body']);

    expect($policy->body)->toBe('Custom body');
});

test('id and timestamps are not mass assignable', function () {
    $policy = PrivacyPolicy::create([
        'body' => 'Test',
        'id' => 999,
    ]);

    expect($policy->id)->not->toBe(999);
});

test('current returns existing policy when one exists', function () {
    $existing = PrivacyPolicy::factory()->create(['body' => 'Existing body']);

    $current = PrivacyPolicy::current();

    expect($current->id)->toBe($existing->id)
        ->and($current->body)->toBe('Existing body');
});

test('current creates a placeholder when no policy exists', function () {
    expect(PrivacyPolicy::count())->toBe(0);

    $current = PrivacyPolicy::current();

    expect($current)->toBeInstanceOf(PrivacyPolicy::class)
        ->and($current->body)->toBe('')
        ->and(PrivacyPolicy::count())->toBe(1);
});

test('current does not create a duplicate when called twice', function () {
    PrivacyPolicy::factory()->create(['body' => 'Seeded']);

    $first = PrivacyPolicy::current();
    $second = PrivacyPolicy::current();

    expect($first->id)->toBe($second->id)
        ->and(PrivacyPolicy::count())->toBe(1);
});

test('bodyHtml converts markdown to html', function () {
    $policy = PrivacyPolicy::factory()->make([
        'body' => "## Heading\n\nA paragraph.",
    ]);

    expect($policy->bodyHtml())->toContain('<h2>Heading</h2>')
        ->and($policy->bodyHtml())->toContain('<p>A paragraph.</p>');
});

test('timestamps are automatically set on creation', function () {
    $policy = PrivacyPolicy::factory()->create();

    expect($policy->created_at)->not->toBeNull()
        ->and($policy->updated_at)->not->toBeNull()
        ->and($policy->created_at->format('Y-m-d'))->toBe('2025-08-26');
});
