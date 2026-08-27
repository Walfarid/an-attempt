<?php

use App\Http\Requests\Dashboard\PublicationRequest;
use App\Models\Publication;
use App\Models\User;

test('the rules method returns the expected validation rules', function () {
    $request = new PublicationRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['citation', 'venue', 'year', 'doi_url'])
        ->and($rules['citation'])->toContain('required', 'string')
        ->and($rules['venue'])->toContain('required', 'string', 'max:255')
        ->and($rules['year'])->toContain('required', 'integer', 'digits:4', 'min:1900')
        ->and($rules['doi_url'])->toContain('required', 'url', 'max:255');
});

test('citation must be a string', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => ['not', 'a', 'string'],
        'venue' => 'Journal of Systems and Software',
        'year' => 2024,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertInvalid('citation');
});

test('venue cannot exceed 255 characters', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => str_repeat('a', 256),
        'year' => 2024,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertInvalid('venue');
});

test('year must be exactly four digits', function (int $year) {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => $year,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertInvalid('year');
})->with([
    'three digits' => 123,
    'five digits' => 12345,
    'two digits' => 24,
]);

test('year must be an integer', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => 'not-an-integer',
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertInvalid('year');
});

test('year validation allows current year and next year', function (int $year) {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => $year,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertRedirect(route('dashboard.publications.index'));
})->with([
    'current year' => now()->year,
    'next year' => now()->year + 1,
]);

test('year validation rejects years before 1900', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => 1899,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertInvalid('year');
});

test('year validation rejects years more than one year in the future', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => now()->year + 2,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertInvalid('year');
});

test('doi_url must be a valid URL', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => 2024,
        'doi_url' => 'not-a-url',
    ])->assertInvalid('doi_url');
});

test('doi_url cannot exceed 255 characters', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => 2024,
        'doi_url' => 'https://doi.org/'.str_repeat('a', 240),
    ])->assertInvalid('doi_url');
});

test('valid data passes validation on store', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A Study in Software Engineering," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => 2024,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertRedirect(route('dashboard.publications.index'));
});

test('valid data passes validation on update', function () {
    $publication = Publication::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/publications/{$publication->id}", [
        'citation' => 'Updated citation text.',
        'venue' => 'IEEE Access',
        'year' => 2023,
        'doi_url' => 'https://doi.org/10.5678/efgh.123',
    ])->assertRedirect(route('dashboard.publications.index'));
});

test('multiple validation errors can occur simultaneously', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => '',
        'venue' => str_repeat('a', 300),
        'year' => 'invalid',
        'doi_url' => 'not-a-url',
    ])->assertInvalid(['citation', 'venue', 'year', 'doi_url']);
});
