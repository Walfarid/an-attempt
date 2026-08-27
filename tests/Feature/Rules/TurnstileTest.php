<?php

use App\Rules\Turnstile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    config(['contact.turnstile_secret_key' => null]);
});

test('validation passes when no secret key is configured', function () {
    $rule = new Turnstile;

    $validator = Validator::make(
        ['captcha' => 'any-token'],
        ['captcha' => $rule],
    );

    expect($validator->passes())->toBeTrue();
});

test('validation passes when secret key is empty string', function () {
    config(['contact.turnstile_secret_key' => '']);

    $rule = new Turnstile;

    $validator = Validator::make(
        ['captcha' => 'any-token'],
        ['captcha' => $rule],
    );

    expect($validator->passes())->toBeTrue();
});

test('validation passes when turnstile api returns success', function () {
    config(['contact.turnstile_secret_key' => 'test-secret']);

    Http::fake([
        'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $rule = new Turnstile;

    $validator = Validator::make(
        ['captcha' => 'valid-token'],
        ['captcha' => $rule],
    );

    expect($validator->passes())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request->method() === 'POST'
            && $request['secret'] === 'test-secret'
            && $request['response'] === 'valid-token';
    });
});

test('validation fails when turnstile api returns failure', function () {
    config(['contact.turnstile_secret_key' => 'test-secret']);

    Http::fake([
        'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ], 200),
    ]);

    $rule = new Turnstile;

    $validator = Validator::make(
        ['captcha' => 'invalid-token'],
        ['captcha' => $rule],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('captcha'))->toBe('The captcha verification failed. Please try again.');
});

test('validation sends client ip to turnstile api', function () {
    config(['contact.turnstile_secret_key' => 'test-secret']);

    Http::fake([
        'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);

    // Make a request to set up the request context with a specific IP
    $this->call('GET', '/', [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);

    $rule = new Turnstile;

    $validator = Validator::make(
        ['captcha' => 'token-with-ip'],
        ['captcha' => $rule],
    );

    expect($validator->passes())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['remoteip'] === '192.168.1.1';
    });
});

test('validation throws exception on connection failure', function () {
    config(['contact.turnstile_secret_key' => 'test-secret']);

    Http::fake([
        'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::failedConnection(),
    ]);

    $rule = new Turnstile;

    $validator = Validator::make(
        ['captcha' => 'token'],
        ['captcha' => $rule],
    );

    // This should throw a ConnectionException
    $validator->passes();
})->throws(ConnectionException::class);

test('validation handles non-json response', function () {
    config(['contact.turnstile_secret_key' => 'test-secret']);

    Http::fake([
        'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response('Not JSON', 500),
    ]);

    $rule = new Turnstile;

    $validator = Validator::make(
        ['captcha' => 'token'],
        ['captcha' => $rule],
    );

    expect($validator->fails())->toBeTrue();
});

test('validation converts token value to string', function () {
    config(['contact.turnstile_secret_key' => 'test-secret']);

    Http::fake([
        'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $rule = new Turnstile;

    $validator = Validator::make(
        ['captcha' => 12345],
        ['captcha' => $rule],
    );

    expect($validator->passes())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['response'] === '12345';
    });
});
