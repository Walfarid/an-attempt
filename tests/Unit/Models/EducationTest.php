<?php

use App\Models\Education;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2025-08-26');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('factory creates a valid education record', function () {
    $education = Education::factory()->create();

    expect($education)->toBeInstanceOf(Education::class)
        ->and($education->school)->not->toBeEmpty()
        ->and($education->degree)->not->toBeEmpty()
        ->and($education->started_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($education->ended_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($education->details)->toBeArray();
});

test('factory ongoing state creates an education without end date', function () {
    $education = Education::factory()->ongoing()->create();

    expect($education->ended_at)->toBeNull()
        ->and($education->started_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('started_at is cast to a date', function () {
    $education = Education::factory()->create(['started_at' => '2020-01-15']);

    expect($education->started_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($education->started_at->format('Y-m-d'))->toBe('2020-01-15');
});

test('ended_at is cast to a date', function () {
    $education = Education::factory()->create(['ended_at' => '2024-06-30']);

    expect($education->ended_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($education->ended_at->format('Y-m-d'))->toBe('2024-06-30');
});

test('details is cast to an array', function () {
    $education = Education::factory()->create([
        'details' => ['Focus on distributed systems', 'Thesis on microservices'],
    ]);

    expect($education->details)->toBeArray()
        ->and($education->details)->toHaveCount(2)
        ->and($education->details[0])->toBe('Focus on distributed systems');
});

test('details can be an empty array', function () {
    $education = Education::factory()->create(['details' => []]);

    expect($education->fresh()->details)->toBe([]);
});

test('ended_at can be null for ongoing education', function () {
    $education = Education::factory()->create(['ended_at' => null]);

    expect($education->fresh()->ended_at)->toBeNull();
});

test('started_at can be null', function () {
    $education = Education::factory()->create(['started_at' => null]);

    expect($education->fresh()->started_at)->toBeNull();
});

test('model uses the educations table explicitly', function () {
    $education = new Education;

    expect($education->getTable())->toBe('educations');
});

test('fillable attributes are mass assignable', function () {
    $education = Education::create([
        'school' => 'MIT',
        'degree' => 'Ph.D. in Computer Science',
        'started_at' => '2020-09-01',
        'ended_at' => '2025-05-15',
        'details' => ['Research in AI'],
        'sort_order' => 10,
    ]);

    expect($education->school)->toBe('MIT')
        ->and($education->degree)->toBe('Ph.D. in Computer Science')
        ->and($education->started_at->format('Y-m-d'))->toBe('2020-09-01')
        ->and($education->ended_at->format('Y-m-d'))->toBe('2025-05-15')
        ->and($education->details)->toBe(['Research in AI'])
        ->and($education->sort_order)->toBe(10);
});

test('id and timestamps are not mass assignable', function () {
    $education = Education::create([
        'school' => 'Stanford',
        'degree' => 'M.Sc.',
        'details' => [],
        'id' => 999,
    ]);

    expect($education->id)->not->toBe(999);
});

test('sort_order defaults to zero via the database', function () {
    $education = Education::create([
        'school' => 'Berkeley',
        'degree' => 'B.Sc.',
        'details' => [],
    ]);

    // The model needs to be refreshed to get the database default
    expect($education->fresh()->sort_order)->toBe(0);
});
