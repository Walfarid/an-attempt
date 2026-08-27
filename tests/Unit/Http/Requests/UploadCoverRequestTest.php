<?php

use App\Http\Requests\Dashboard\UploadCoverRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->request = new UploadCoverRequest;
});

// Rules structure tests

test('rules returns the expected validation structure', function () {
    $rules = $this->request->rules();

    expect($rules)->toHaveKey('cover')
        ->and($rules['cover'])->toContain('required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096');
});

test('the cover field is required', function () {
    $rules = $this->request->rules();

    expect($rules['cover'])->toContain('required');
});

test('the cover must be a valid image file', function () {
    $rules = $this->request->rules();

    expect($rules['cover'])->toContain('image');
});

test('the cover must be jpg, jpeg, png, or webp', function () {
    $rules = $this->request->rules();

    expect($rules['cover'])->toContain('mimes:jpg,jpeg,png,webp');
});

test('the cover has a maximum size of 4096 kilobytes', function () {
    $rules = $this->request->rules();

    expect($rules['cover'])->toContain('max:4096');
});

// Validation failure tests

test('validation fails when cover is missing', function () {
    $validator = Validator::make([], $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

test('validation fails when cover is not a valid image type', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

test('validation fails when cover exceeds max size', function () {
    // Create an image larger than 4096 KB (4.1 MB)
    $file = UploadedFile::fake()->image('large.jpg')->size(4100);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

test('validation fails when cover is not an image file', function () {
    $file = UploadedFile::fake()->create('script.js', 100, 'application/javascript');

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

test('validation fails when cover has unsupported mime type', function () {
    // GIF is not in the allowed mimes list
    $file = UploadedFile::fake()->image('animation.gif', 100, 100);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

test('validation fails when cover is a bmp file', function () {
    // BMP is not in the allowed mimes list
    $file = UploadedFile::fake()->image('image.bmp', 100, 100);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

test('validation fails when cover is a tiff file', function () {
    // TIFF is not in the allowed mimes list
    $file = UploadedFile::fake()->image('image.tiff', 100, 100);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

test('validation fails when cover is an svg file', function () {
    // SVG is not in the allowed mimes list
    $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

// Validation passing tests

test('validation passes with a valid jpg image', function () {
    $file = UploadedFile::fake()->image('cover.jpg', 1920, 1080);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('validation passes with a valid jpeg image', function () {
    $file = UploadedFile::fake()->image('cover.jpeg', 800, 600);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('validation passes with a valid png image', function () {
    $file = UploadedFile::fake()->image('cover.png', 1200, 800);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('validation passes with a valid webp image', function () {
    $file = UploadedFile::fake()->image('cover.webp', 1600, 900);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('validation passes with cover at exactly 4096 kilobytes', function () {
    $file = UploadedFile::fake()->image('cover.png')->size(4096);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('validation passes with a small image', function () {
    $file = UploadedFile::fake()->image('cover.jpg', 100, 100)->size(50);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('validation passes with various valid dimensions', function (int $width, int $height) {
    $file = UploadedFile::fake()->image('cover.jpg', $width, $height);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeFalse();
})->with([
    'small square' => [100, 100],
    'standard HD' => [1280, 720],
    'full HD' => [1920, 1080],
    '4K' => [3840, 2160],
    'portrait' => [800, 1200],
    'wide banner' => [2400, 600],
]);

test('validation fails with cover just over max size', function () {
    // 4097 KB - just 1 KB over the limit
    $file = UploadedFile::fake()->image('cover.jpg')->size(4097);

    $validator = Validator::make(
        ['cover' => $file],
        $this->request->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('cover'))->toBeTrue();
});

// Authorization test

test('request uses default authorization (no authorize method defined)', function () {
    $request = new UploadCoverRequest;

    // In Laravel 11, FormRequest doesn't define authorize() by default.
    // Authorization is handled via route middleware or policies.
    expect(method_exists($request, 'authorize'))->toBeFalse();
});
