<?php

use App\Models\Experience;
use Carbon\CarbonImmutable;

test('factory creates valid experience with defaults', function () {
    $experience = Experience::factory()->create();

    expect($experience->role)->toBeString()
        ->and($experience->company)->toBeString()
        ->and($experience->location)->toBeString()
        ->and($experience->started_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($experience->summary)->toBeString()
        ->and($experience->highlights)->toBeArray()
        ->and($experience->ended_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('factory current state creates experience with no end date', function () {
    $experience = Experience::factory()->current()->create();

    expect($experience->ended_at)->toBeNull();
});

test('started_at is cast to a date', function () {
    $experience = Experience::factory()->create([
        'started_at' => '2019-03-15',
    ]);

    expect($experience->started_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($experience->started_at->year)->toBe(2019)
        ->and($experience->started_at->month)->toBe(3)
        ->and($experience->started_at->day)->toBe(15);
});

test('ended_at is cast to a date when set', function () {
    $experience = Experience::factory()->create([
        'ended_at' => '2021-08-31',
    ]);

    expect($experience->ended_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($experience->ended_at->year)->toBe(2021)
        ->and($experience->ended_at->month)->toBe(8)
        ->and($experience->ended_at->day)->toBe(31);
});

test('ended_at remains null when not set', function () {
    $experience = Experience::factory()->current()->create();

    expect($experience->ended_at)->toBeNull();
});

test('highlights are cast to an array', function () {
    $experience = Experience::factory()->create([
        'highlights' => ['Built APIs', 'Led team', 'Improved performance'],
    ]);

    expect($experience->highlights)->toBeArray()
        ->and($experience->highlights)->toHaveCount(3)
        ->and($experience->highlights)->toContain('Built APIs', 'Led team');
});

test('highlights can be set and retrieved as array', function () {
    $experience = Experience::factory()->make([
        'highlights' => ['First highlight', 'Second highlight'],
    ]);
    $experience->save();
    $experience->refresh();

    expect($experience->highlights)->toBe(['First highlight', 'Second highlight']);
});

test('fillable attributes can be mass assigned', function () {
    $data = [
        'role' => 'Senior Developer',
        'company' => 'Tech Corp',
        'location' => 'Berlin, Germany',
        'started_at' => '2020-01-15',
        'ended_at' => '2023-06-30',
        'summary' => 'Led development of core platform.',
        'highlights' => ['Scaled infrastructure', 'Mentored juniors'],
    ];

    $experience = Experience::create($data);

    expect($experience->role)->toBe('Senior Developer')
        ->and($experience->company)->toBe('Tech Corp')
        ->and($experience->location)->toBe('Berlin, Germany')
        ->and($experience->started_at->toDateString())->toBe('2020-01-15')
        ->and($experience->ended_at->toDateString())->toBe('2023-06-30')
        ->and($experience->summary)->toBe('Led development of core platform.')
        ->and($experience->highlights)->toBe(['Scaled infrastructure', 'Mentored juniors']);
});
