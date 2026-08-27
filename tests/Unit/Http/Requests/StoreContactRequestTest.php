<?php

use App\Http\Requests\StoreContactRequest;
use App\Rules\Turnstile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('rules returns the expected validation structure', function () {
    $request = new StoreContactRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['name', 'email', 'subject', 'body', 'website', 'turnstile_token'])
        ->and($rules['name'])->toContain('required', 'string', 'max:255')
        ->and($rules['email'])->toContain('required', 'email', 'max:255')
        ->and($rules['subject'])->toContain('nullable', 'string', 'max:255')
        ->and($rules['body'])->toContain('required', 'string', 'max:5000')
        ->and($rules['website'])->toContain('prohibited')
        ->and($rules['turnstile_token'])->toContain('nullable');
});

test('turnstile_token rule includes turnstile custom rule', function () {
    $request = new StoreContactRequest;

    $rules = $request->rules();

    // Check that turnstile_token contains a Turnstile instance
    $hasTurnstileRule = collect($rules['turnstile_token'])->contains(
        fn ($rule) => $rule instanceof Turnstile
    );

    expect($hasTurnstileRule)->toBeTrue();
});

test('name is required', function () {
    $validator = Validator::make(
        ['email' => 'jane@example.com', 'body' => 'Hello world'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue();
});

test('name must be a string', function () {
    $validator = Validator::make(
        ['name' => ['array'], 'email' => 'jane@example.com', 'body' => 'Hello world'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue();
});

test('name has a maximum length of 255 characters', function () {
    $rules = (new StoreContactRequest)->rules();

    $tooLong = Validator::make(
        ['name' => str_repeat('a', 256), 'email' => 'jane@example.com', 'body' => 'Hello world'],
        $rules
    );

    $justRight = Validator::make(
        ['name' => str_repeat('a', 255), 'email' => 'jane@example.com', 'body' => 'Hello world'],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('name'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

test('email is required', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'body' => 'Hello world'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});

test('email must be a valid email address', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'not-an-email', 'body' => 'Hello world'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});

test('email has a maximum length of 255 characters', function () {
    $rules = (new StoreContactRequest)->rules();

    // Email over 255 chars (250 local chars + @example.com = 262 total)
    $tooLong = Validator::make(
        ['name' => 'Jane', 'email' => str_repeat('a', 250).'@example.com', 'body' => 'Hello'],
        $rules
    );

    // Email at exactly 255 chars (245 local chars + @example.com = 257, need to be more careful)
    // Actually, let's use 245 'a' chars + '@example.com' (12 chars) = 257, still too long
    // Let's use 243 'a' chars + '@example.com' (12 chars) = 255 chars total
    $justRight = Validator::make(
        ['name' => 'Jane', 'email' => str_repeat('a', 243).'@example.com', 'body' => 'Hello'],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('email'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

test('subject is optional', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'jane@example.com', 'body' => 'Hello world'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('subject can be null', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'jane@example.com', 'body' => 'Hello world', 'subject' => null],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('subject can be an empty string', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'jane@example.com', 'body' => 'Hello world', 'subject' => ''],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('subject must be a string when provided', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'jane@example.com', 'body' => 'Hello world', 'subject' => ['array']],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('subject'))->toBeTrue();
});

test('subject has a maximum length of 255 characters', function () {
    $rules = (new StoreContactRequest)->rules();

    $tooLong = Validator::make(
        ['name' => 'Jane', 'email' => 'jane@example.com', 'body' => 'Hello', 'subject' => str_repeat('a', 256)],
        $rules
    );

    $justRight = Validator::make(
        ['name' => 'Jane', 'email' => 'jane@example.com', 'body' => 'Hello', 'subject' => str_repeat('a', 255)],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('subject'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

test('body is required', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'jane@example.com'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('body'))->toBeTrue();
});

test('body must be a string', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'jane@example.com', 'body' => ['array']],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('body'))->toBeTrue();
});

test('body has a maximum length of 5000 characters', function () {
    $rules = (new StoreContactRequest)->rules();

    $tooLong = Validator::make(
        ['name' => 'Jane', 'email' => 'jane@example.com', 'body' => str_repeat('a', 5001)],
        $rules
    );

    $justRight = Validator::make(
        ['name' => 'Jane', 'email' => 'jane@example.com', 'body' => str_repeat('a', 5000)],
        $rules
    );

    expect($tooLong->fails())->toBeTrue()
        ->and($tooLong->errors()->has('body'))->toBeTrue()
        ->and($justRight->fails())->toBeFalse();
});

test('website field is prohibited', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'jane@example.com', 'body' => 'Hello world', 'website' => 'spam'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('website'))->toBeTrue();
});

test('website field rejects any non-empty value', function () {
    $rules = (new StoreContactRequest)->rules();

    $withValue = Validator::make(
        ['name' => 'Jane', 'email' => 'jane@example.com', 'body' => 'Hello', 'website' => 'any-value'],
        $rules
    );

    expect($withValue->fails())->toBeTrue()
        ->and($withValue->errors()->has('website'))->toBeTrue();
});

test('turnstile_token is nullable', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'jane@example.com', 'body' => 'Hello world'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('turnstile_token can be an empty string', function () {
    $validator = Validator::make(
        ['name' => 'Jane Visitor', 'email' => 'jane@example.com', 'body' => 'Hello world', 'turnstile_token' => ''],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('valid contact data passes validation', function () {
    $validator = Validator::make(
        [
            'name' => 'Jane Visitor',
            'email' => 'jane@example.com',
            'subject' => 'Hello there',
            'body' => 'I would like to work with you.',
        ],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('valid contact data with minimal fields passes validation', function () {
    $validator = Validator::make(
        [
            'name' => 'Jane Visitor',
            'email' => 'jane@example.com',
            'body' => 'Hello world',
        ],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('missing required fields triggers multiple validation errors', function () {
    $validator = Validator::make([], (new StoreContactRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue()
        ->and($validator->errors()->has('body'))->toBeTrue();
});

test('all invalid fields are reported simultaneously', function () {
    $validator = Validator::make(
        [
            'name' => '',
            'email' => 'not-an-email',
            'body' => '',
            'website' => 'spam',
            'subject' => ['array'],
        ],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue()
        ->and($validator->errors()->has('body'))->toBeTrue()
        ->and($validator->errors()->has('website'))->toBeTrue()
        ->and($validator->errors()->has('subject'))->toBeTrue();
});

test('subject can contain unicode characters', function () {
    $validator = Validator::make(
        [
            'name' => 'Jane Visitor',
            'email' => 'jane@example.com',
            'subject' => 'Héllo Wörld 日本語 🎉',
            'body' => 'Hello world',
        ],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('body can contain unicode characters', function () {
    $validator = Validator::make(
        [
            'name' => 'Jane Visitor',
            'email' => 'jane@example.com',
            'body' => 'Héllo Wörld 日本語 🎉 '.str_repeat('x', 4980),
        ],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('name can contain unicode characters', function () {
    $validator = Validator::make(
        [
            'name' => 'José García 日本語',
            'email' => 'jane@example.com',
            'body' => 'Hello world',
        ],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('email validation rejects common invalid formats', function (string $invalidEmail) {
    $validator = Validator::make(
        ['name' => 'Jane', 'email' => $invalidEmail, 'body' => 'Hello'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
})->with([
    'missing @' => 'plain-text',
    'missing domain' => 'user@',
    'missing local part' => '@example.com',
    'multiple @' => 'user@@example.com',
]);

test('email validation accepts valid formats', function (string $validEmail) {
    $validator = Validator::make(
        ['name' => 'Jane', 'email' => $validEmail, 'body' => 'Hello'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
})->with([
    'standard' => 'user@example.com',
    'subdomain' => 'user@mail.example.com',
    'plus tag' => 'user+tag@example.com',
    'dash' => 'user-name@example.com',
    'dots in local' => 'first.last@example.com',
]);

test('body can be exactly at max length boundary', function () {
    $validator = Validator::make(
        ['name' => 'Jane', 'email' => 'jane@example.com', 'body' => str_repeat('a', 5000)],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('name can be exactly at max length boundary', function () {
    $validator = Validator::make(
        ['name' => str_repeat('a', 255), 'email' => 'jane@example.com', 'body' => 'Hello'],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('subject can be exactly at max length boundary', function () {
    $validator = Validator::make(
        ['name' => 'Jane', 'email' => 'jane@example.com', 'body' => 'Hello', 'subject' => str_repeat('a', 255)],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});

test('body can contain newlines and special characters', function () {
    $validator = Validator::make(
        [
            'name' => 'Jane Visitor',
            'email' => 'jane@example.com',
            'body' => "Line 1\nLine 2\r\nLine 3\tTabbed\n\nParagraph break",
        ],
        (new StoreContactRequest)->rules()
    );

    expect($validator->fails())->toBeFalse();
});
