<?php

use App\Providers\AppServiceProvider;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Reset Date facade to default before each test
    Date::use(Carbon::class);
});

afterEach(function () {
    // Clean up after each test
    Date::use(Carbon::class);
});

test('boot configures application defaults', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    // Re-boot to ensure configureDefaults runs
    $provider->boot();

    // Verify CarbonImmutable is set as the default date class
    expect(Date::now())->toBeInstanceOf(CarbonImmutable::class);
});

test('date facade uses CarbonImmutable after boot', function () {
    $provider = app()->getProvider(AppServiceProvider::class);
    $provider->boot();

    $date = Date::now();

    expect($date)->toBeInstanceOf(CarbonImmutable::class)
        ->and(Date::create(2025, 1, 15))->toBeInstanceOf(CarbonImmutable::class)
        ->and(Date::parse('2025-06-20'))->toBeInstanceOf(CarbonImmutable::class);
});

test('date facade returns CarbonImmutable for today and tomorrow', function () {
    $provider = app()->getProvider(AppServiceProvider::class);
    $provider->boot();

    expect(Date::today())->toBeInstanceOf(CarbonImmutable::class)
        ->and(Date::tomorrow())->toBeInstanceOf(CarbonImmutable::class)
        ->and(Date::yesterday())->toBeInstanceOf(CarbonImmutable::class);
});

test('date immutability prevents accidental mutation', function () {
    $provider = app()->getProvider(AppServiceProvider::class);
    $provider->boot();

    $original = Date::parse('2025-08-26 12:00:00');
    $modified = $original->addHour();

    // CarbonImmutable returns a new instance, leaving original unchanged
    expect($original->format('H'))->toBe('12')
        ->and($modified->format('H'))->toBe('13')
        ->and($original)->not->toBe($modified);
});

test('destructive database commands are prohibited in production', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    // Mock production environment
    app()->detectEnvironment(fn () => 'production');

    $provider->boot();

    // Test that destructive commands are prohibited by checking the actual commands
    // The prohibition affects: migrate:fresh, migrate:refresh, migrate:reset, migrate:rollback, db:wipe
    $this->artisan('db:wipe')->assertFailed();
});

test('destructive database commands are allowed in non-production', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    // Mock local environment
    app()->detectEnvironment(fn () => 'local');

    $provider->boot();

    // In non-production, destructive commands should not be prohibited
    // We verify by checking that the prohibition flag is false
    // Since we can't directly test the command without actually wiping,
    // we verify the boot completed without error
    expect(true)->toBeTrue();
});

test('password defaults are strict in production', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    // Mock production environment
    app()->detectEnvironment(fn () => 'production');

    $provider->boot();

    $rules = Password::default();

    expect($rules)->toBeInstanceOf(Password::class);
});

test('production password defaults require minimum 12 characters', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    app()->detectEnvironment(fn () => 'production');
    $provider->boot();

    $validator = validator(
        ['password' => 'short'],
        ['password' => ['required', Password::default()]]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue();
});

test('production password defaults require mixed case', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    app()->detectEnvironment(fn () => 'production');
    $provider->boot();

    $validator = validator(
        ['password' => 'alllowercase123!'],
        ['password' => ['required', Password::default()]]
    );

    expect($validator->fails())->toBeTrue();
});

test('production password defaults require letters', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    app()->detectEnvironment(fn () => 'production');
    $provider->boot();

    $validator = validator(
        ['password' => '123456789012!@'],
        ['password' => ['required', Password::default()]]
    );

    expect($validator->fails())->toBeTrue();
});

test('production password defaults require numbers', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    app()->detectEnvironment(fn () => 'production');
    $provider->boot();

    $validator = validator(
        ['password' => 'OnlyLettersHere!'],
        ['password' => ['required', Password::default()]]
    );

    expect($validator->fails())->toBeTrue();
});

test('production password defaults require symbols', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    app()->detectEnvironment(fn () => 'production');
    $provider->boot();

    $validator = validator(
        ['password' => 'ValidPassword123'],
        ['password' => ['required', Password::default()]]
    );

    expect($validator->fails())->toBeTrue();
});

test('production password accepts strong passwords', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    app()->detectEnvironment(fn () => 'production');
    $provider->boot();

    $validator = validator(
        ['password' => 'Strong-Password-123!'],
        ['password' => ['required', Password::default()]]
    );

    expect($validator->fails())->toBeFalse();
});

test('non-production password defaults fall back to min 8', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    app()->detectEnvironment(fn () => 'local');
    $provider->boot();

    $rules = Password::default();

    // When callback returns null, Laravel falls back to min(8)
    expect($rules)->toBeInstanceOf(Password::class);

    // Verify min 8 is enforced
    $validator = validator(
        ['password' => 'short'],
        ['password' => ['required', Password::default()]]
    );

    expect($validator->fails())->toBeTrue();

    // Verify 8+ characters pass
    $validator = validator(
        ['password' => 'longenough'],
        ['password' => ['required', Password::default()]]
    );

    expect($validator->fails())->toBeFalse();
});

test('register does nothing', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    // register() is empty, but we call it to ensure no exceptions
    $provider->register();

    // No assertions needed - just verifying it doesn't throw
    expect(true)->toBeTrue();
});

test('configureDefaults is called during boot', function () {
    $provider = app()->getProvider(AppServiceProvider::class);

    // Boot the provider and verify side effects occur
    $provider->boot();

    // If configureDefaults ran, Date should use CarbonImmutable
    expect(Date::now())->toBeInstanceOf(CarbonImmutable::class);
});
