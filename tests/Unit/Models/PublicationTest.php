<?php

use App\Models\Publication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('factory creates a valid publication instance', function () {
    $publication = Publication::factory()->create();

    expect($publication)->toBeInstanceOf(Publication::class)
        ->and($publication->exists)->toBeTrue()
        ->and($publication->citation)->not->toBeEmpty()
        ->and($publication->venue)->not->toBeEmpty()
        ->and($publication->year)->toBeInt()
        ->and($publication->doi_url)->toStartWith('https://doi.org/');
});

test('factory state produces plausible years', function () {
    $publication = Publication::factory()->create();

    expect($publication->year)->toBeGreaterThanOrEqual(2018)
        ->and($publication->year)->toBeLessThanOrEqual(2025);
});

test('fillable attributes are correctly defined', function () {
    $publication = new Publication;
    $fillable = $publication->getFillable();

    expect($fillable)->toContain('citation', 'venue', 'year', 'doi_url', 'sort_order')
        ->and(count($fillable))->toBe(5);
});

test('mass assignment works for all fillable attributes', function () {
    $data = [
        'citation' => 'Doe J. "A Study in Software Engineering," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => 2024,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
        'sort_order' => 1,
    ];

    $publication = Publication::create($data);

    expect($publication->citation)->toBe('Doe J. "A Study in Software Engineering," 2024.')
        ->and($publication->venue)->toBe('Journal of Systems and Software')
        ->and($publication->year)->toBe(2024)
        ->and($publication->doi_url)->toBe('https://doi.org/10.1234/abcd.567')
        ->and($publication->sort_order)->toBe(1);
});

test('model can be updated with fillable attributes', function () {
    $publication = Publication::factory()->create();

    $publication->update([
        'citation' => 'Updated citation.',
        'venue' => 'IEEE Access',
        'year' => 2023,
        'doi_url' => 'https://doi.org/10.5678/efgh.123',
        'sort_order' => 5,
    ]);

    expect($publication->fresh()->citation)->toBe('Updated citation.')
        ->and($publication->fresh()->venue)->toBe('IEEE Access')
        ->and($publication->fresh()->year)->toBe(2023)
        ->and($publication->fresh()->doi_url)->toBe('https://doi.org/10.5678/efgh.123')
        ->and($publication->fresh()->sort_order)->toBe(5);
});

test('model can be deleted from database', function () {
    $publication = Publication::factory()->create();
    $id = $publication->id;

    $publication->delete();

    expect(Publication::find($id))->toBeNull();
});

test('multiple publications can coexist in database', function () {
    $count = 3;
    Publication::factory()->count($count)->create();

    expect(Publication::count())->toBe($count);
});

test('factory generates valid doi urls', function () {
    $publication = Publication::factory()->make();

    expect($publication->doi_url)->toMatch('/^https:\/\/doi\.org\/10\.\d{4}\/[a-zA-Z0-9]+\.[a-zA-Z0-9]+$/');
});

test('factory citation contains author names', function () {
    $publication = Publication::factory()->make();

    expect($publication->citation)->toContain(' et al.');
});

test('factory venue is from predefined list', function () {
    $venues = [
        'Journal of Systems and Software',
        'IEEE Access',
        'ASE Conference Proceedings',
    ];

    $publication = Publication::factory()->make();

    expect($venues)->toContain($publication->venue);
});

test('id and timestamps are not mass assignable', function () {
    $publication = Publication::create([
        'citation' => 'Test citation.',
        'venue' => 'Test Venue',
        'year' => 2024,
        'doi_url' => 'https://doi.org/10.1234/test.123',
        'id' => 999,
    ]);

    expect($publication->id)->not->toBe(999);
});

test('sort_order defaults to zero in database when not specified', function () {
    $publication = Publication::factory()->create();

    expect($publication->fresh()->sort_order)->toBe(0);
});

test('sort_order can be set explicitly', function () {
    $publication = Publication::factory()->create(['sort_order' => 10]);

    expect($publication->fresh()->sort_order)->toBe(10);
});

test('factory generates unique citations', function () {
    $publications = Publication::factory()->count(3)->create();

    $citations = $publications->pluck('citation')->toArray();

    expect(count($citations))->toBe(count(array_unique($citations)));
});

test('factory respects explicitly set citation', function () {
    $publication = Publication::factory()->create(['citation' => 'Explicit citation text.']);

    expect($publication->citation)->toBe('Explicit citation text.');
});

test('factory respects explicitly set venue', function () {
    $publication = Publication::factory()->create(['venue' => 'Explicit Venue']);

    expect($publication->venue)->toBe('Explicit Venue');
});

test('factory respects explicitly set year', function () {
    $publication = Publication::factory()->create(['year' => 2020]);

    expect($publication->year)->toBe(2020);
});

test('factory respects explicitly set doi_url', function () {
    $publication = Publication::factory()->create(['doi_url' => 'https://doi.org/10.9999/explicit']);

    expect($publication->doi_url)->toBe('https://doi.org/10.9999/explicit');
});

test('publication can be retrieved by id', function () {
    $publication = Publication::factory()->create();

    $found = Publication::find($publication->id);

    expect($found->id)->toBe($publication->id)
        ->and($found->citation)->toBe($publication->citation);
});

test('publication uses has factory trait', function () {
    expect(method_exists(Publication::class, 'factory'))->toBeTrue();
});

test('publication extends model', function () {
    $publication = Publication::factory()->create();

    expect($publication)->toBeInstanceOf('Illuminate\Database\Eloquent\Model');
});
