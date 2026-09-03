<?php

use App\Http\Requests\Dashboard\UpdatePrivacyPolicyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->request = new UpdatePrivacyPolicyRequest;
});

test('rules returns the expected validation structure', function () {
    $rules = $this->request->rules();

    expect($rules)->toHaveKey('body')
        ->and($rules['body'])->toContain('required', 'string');
});

test('validation passes with a valid body', function () {
    $validator = Validator::make(['body' => 'Privacy policy text.'], $this->request->rules());

    expect($validator->fails())->toBeFalse();
});

test('validation fails when body is missing', function () {
    $validator = Validator::make([], $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('body'))->toBeTrue();
});

test('request uses default authorization (no authorize method defined)', function () {
    expect(method_exists($this->request, 'authorize'))->toBeFalse();
});
