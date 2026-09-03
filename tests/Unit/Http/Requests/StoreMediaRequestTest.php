<?php

use App\Http\Requests\Dashboard\StoreMediaRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->request = new StoreMediaRequest;
});

test('rules returns the expected validation structure', function () {
    $rules = $this->request->rules();

    expect($rules)->toHaveKey('file')
        ->and($rules['file'])->toContain('required', 'max:4096');
});

test('validation fails when file is missing', function () {
    $validator = Validator::make([], $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('file'))->toBeTrue();
});

test('validation passes with an svg image', function () {
    $file = UploadedFile::fake()->create('logo.svg', 100, 'image/svg+xml');

    $validator = Validator::make(['file' => $file], $this->request->rules());

    expect($validator->fails())->toBeFalse();
});

test('validation passes with a regular image', function () {
    $file = UploadedFile::fake()->image('photo.png', 800, 600);

    $validator = Validator::make(['file' => $file], $this->request->rules());

    expect($validator->fails())->toBeFalse();
});

test('validation fails for a non-image file', function () {
    $file = UploadedFile::fake()->create('script.js', 100, 'application/javascript');

    $validator = Validator::make(['file' => $file], $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('file'))->toBeTrue();
});

test('request uses default authorization (no authorize method defined)', function () {
    expect(method_exists($this->request, 'authorize'))->toBeFalse();
});
