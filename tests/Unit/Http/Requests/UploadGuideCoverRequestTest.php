<?php

use App\Http\Requests\Dashboard\UploadGuideCoverRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->request = new UploadGuideCoverRequest;
});

test('rules returns the expected validation structure', function () {
    $rules = $this->request->rules();

    expect($rules)->toHaveKey('cover')
        ->and($rules['cover'])->toContain('required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096');
});

test('validation fails when cover is missing', function () {
    $validator = Validator::make([], $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

test('validation fails when cover is an svg file', function () {
    $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

test('validation passes with a valid image', function () {
    $file = UploadedFile::fake()->image('cover.png', 1200, 800);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('request uses default authorization (no authorize method defined)', function () {
    expect(method_exists($this->request, 'authorize'))->toBeFalse();
});
