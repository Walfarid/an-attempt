<?php

use App\Http\Requests\Dashboard\StoreScreenshotRequest;

beforeEach(function () {
    $this->request = new StoreScreenshotRequest;
});

// Unit tests: rules() method returns expected validation rules
test('the image is required', function () {
    $rules = $this->request->rules();

    expect($rules['image'])->toContain('required');
});

test('the image must be a valid image file', function () {
    $rules = $this->request->rules();

    expect($rules['image'])->toContain('image');
});

test('the image must be jpg, jpeg, png, or webp', function () {
    $rules = $this->request->rules();

    expect($rules['image'])->toContain('mimes:jpg,jpeg,png,webp');
});

test('the image has a maximum size of 4096 kilobytes', function () {
    $rules = $this->request->rules();

    expect($rules['image'])->toContain('max:4096');
});

test('the alt field is nullable', function () {
    $rules = $this->request->rules();

    expect($rules['alt'])->toContain('nullable');
});

test('the alt field must be a string', function () {
    $rules = $this->request->rules();

    expect($rules['alt'])->toContain('string');
});

test('the alt field has a maximum length of 255 characters', function () {
    $rules = $this->request->rules();

    expect($rules['alt'])->toContain('max:255');
});
