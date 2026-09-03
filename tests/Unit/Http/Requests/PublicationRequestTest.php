<?php

use App\Http\Requests\Dashboard\PublicationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->request = new PublicationRequest;
});

test('rules returns the expected validation structure', function () {
    $rules = $this->request->rules();

    expect($rules)->toHaveKeys(['citation', 'venue', 'year', 'doi_url'])
        ->and($rules['citation'])->toContain('required', 'string')
        ->and($rules['venue'])->toContain('required', 'string', 'max:255')
        ->and($rules['year'])->toContain('required', 'integer', 'digits:4', 'min:1900', 'max:'.(now()->year + 1))
        ->and($rules['doi_url'])->toContain('required', 'url', 'max:255');
});

test('validation passes with a valid publication payload', function () {
    $validator = Validator::make([
        'citation' => 'Smith, J. (2024). A study.',
        'venue' => 'Journal of Testing',
        'year' => 2024,
        'doi_url' => 'https://doi.org/10.1000/xyz',
    ], $this->request->rules());

    expect($validator->fails())->toBeFalse();
});

test('validation fails when required fields are missing', function () {
    $validator = Validator::make([], $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('citation'))->toBeTrue()
        ->and($validator->errors()->has('venue'))->toBeTrue()
        ->and($validator->errors()->has('year'))->toBeTrue()
        ->and($validator->errors()->has('doi_url'))->toBeTrue();
});

test('validation fails with a non-url doi', function () {
    $validator = Validator::make([
        'citation' => 'Smith, J. (2024). A study.',
        'venue' => 'Journal of Testing',
        'year' => 2024,
        'doi_url' => 'not-a-url',
    ], $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('doi_url'))->toBeTrue();
});

test('request uses default authorization (no authorize method defined)', function () {
    expect(method_exists($this->request, 'authorize'))->toBeFalse();
});
