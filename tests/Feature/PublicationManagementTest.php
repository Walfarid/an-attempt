<?php

use App\Models\Publication;
use App\Models\User;

test('guests are redirected from the publications pages', function () {
    $this->get('/dashboard/publications')->assertRedirect('/login');

    $this->post('/dashboard/publications', ['citation' => 'x'])
        ->assertRedirect('/login');
});

test('users see the publications list', function () {
    Publication::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard/publications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Publications'));
});

test('users can create a publication', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => 'Journal of Systems and Software',
        'year' => 2024,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertRedirect(route('dashboard.publications.index'));

    expect(Publication::where('venue', 'Journal of Systems and Software')->exists())->toBeTrue();
});

test('publications require a citation, venue, year, and DOI URL', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [])
        ->assertInvalid(['citation', 'venue', 'year', 'doi_url']);
});

test('publication years must be plausible four-digit numbers', function (int $year) {
    $this->actingAs(User::factory()->create());

    $this->post('/dashboard/publications', [
        'citation' => 'Doe J. "A study," 2024.',
        'venue' => 'Venue',
        'year' => $year,
        'doi_url' => 'https://doi.org/10.1234/abcd.567',
    ])->assertInvalid('year');
})->with([
    'too early' => 1899,
    'too late' => (int) date('Y') + 2,
]);

test('users can update a publication', function () {
    $publication = Publication::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->put("/dashboard/publications/{$publication->id}", [
        'citation' => 'Updated citation.',
        'venue' => $publication->venue,
        'year' => $publication->year,
        'doi_url' => $publication->doi_url,
    ])->assertRedirect(route('dashboard.publications.index'));

    expect($publication->fresh()->citation)->toBe('Updated citation.');
});

test('users can delete a publication', function () {
    $publication = Publication::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->delete("/dashboard/publications/{$publication->id}")
        ->assertRedirect(route('dashboard.publications.index'));

    expect(Publication::find($publication->id))->toBeNull();
});
