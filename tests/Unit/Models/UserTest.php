<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2025-08-26 12:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('factory creates a valid user', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->not->toBeEmpty()
        ->and($user->email)->not->toBeEmpty()
        ->and($user->email_verified_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($user->workos_id)->not->toBeEmpty()
        ->and($user->remember_token)->not->toBeEmpty()
        ->and($user->avatar)->toBe('');
});

test('factory unverified state creates a user without email verification', function () {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
});

test('email_verified_at is cast to a datetime', function () {
    $user = User::factory()->create(['email_verified_at' => '2025-08-15 09:00:00']);

    expect($user->email_verified_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($user->email_verified_at->format('Y-m-d H:i:s'))->toBe('2025-08-15 09:00:00');
});

test('email_verified_at can be null', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('created_at is cast to a datetime', function () {
    $user = User::factory()->create(['created_at' => '2025-08-15 09:00:00']);

    expect($user->created_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($user->created_at->format('Y-m-d H:i:s'))->toBe('2025-08-15 09:00:00');
});

test('updated_at is cast to a datetime', function () {
    $user = User::factory()->create(['updated_at' => '2025-08-15 09:00:00']);

    expect($user->updated_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($user->updated_at->format('Y-m-d H:i:s'))->toBe('2025-08-15 09:00:00');
});

test('fillable attributes are mass assignable', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'workos_id' => 'workos-123',
        'avatar' => 'https://example.com/avatar.png',
    ]);

    expect($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->workos_id)->toBe('workos-123')
        ->and($user->avatar)->toBe('https://example.com/avatar.png');
});

test('id and timestamps are not mass assignable', function () {
    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'workos_id' => 'workos-456',
        'avatar' => '',
        'id' => 999,
    ]);

    expect($user->id)->not->toBe(999);
});

test('workos_id is hidden from array serialization', function () {
    $user = User::factory()->create(['workos_id' => 'secret-workos-id']);

    expect($user->toArray())->not->toHaveKey('workos_id');
});

test('remember_token is hidden from array serialization', function () {
    $user = User::factory()->create(['remember_token' => 'secret-token']);

    expect($user->toArray())->not->toHaveKey('remember_token');
});

test('email is visible in array serialization', function () {
    $user = User::factory()->create(['email' => 'public@example.com']);

    expect($user->toArray())->toHaveKey('email')
        ->and($user->toArray()['email'])->toBe('public@example.com');
});

test('name is visible in array serialization', function () {
    $user = User::factory()->create(['name' => 'Public Name']);

    expect($user->toArray())->toHaveKey('name')
        ->and($user->toArray()['name'])->toBe('Public Name');
});

test('avatar is visible in array serialization', function () {
    $user = User::factory()->create(['avatar' => 'https://example.com/avatar.png']);

    expect($user->toArray())->toHaveKey('avatar')
        ->and($user->toArray()['avatar'])->toBe('https://example.com/avatar.png');
});

test('email must be unique', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $this->expectException('Illuminate\Database\QueryException');

    User::factory()->create(['email' => 'duplicate@example.com']);
});

test('workos_id must be unique', function () {
    User::factory()->create(['workos_id' => 'duplicate-workos-id']);

    $this->expectException('Illuminate\Database\QueryException');

    User::factory()->create(['workos_id' => 'duplicate-workos-id']);
});

test('factory generates unique emails to avoid collisions', function () {
    $users = User::factory()->count(3)->create();

    $emails = $users->pluck('email')->toArray();

    expect(count($emails))->toBe(count(array_unique($emails)));
});

test('factory generates unique workos_ids to avoid collisions', function () {
    $users = User::factory()->count(3)->create();

    $workosIds = $users->pluck('workos_id')->toArray();

    expect(count($workosIds))->toBe(count(array_unique($workosIds)));
});

test('factory respects explicitly set email', function () {
    $user = User::factory()->create(['email' => 'explicit@example.com']);

    expect($user->email)->toBe('explicit@example.com');
});

test('factory respects explicitly set name', function () {
    $user = User::factory()->create(['name' => 'Explicit Name']);

    expect($user->name)->toBe('Explicit Name');
});

test('factory respects explicitly set workos_id', function () {
    $user = User::factory()->create(['workos_id' => 'explicit-workos-id']);

    expect($user->workos_id)->toBe('explicit-workos-id');
});

test('factory respects explicitly set avatar', function () {
    $user = User::factory()->create(['avatar' => 'https://explicit.com/avatar.png']);

    expect($user->avatar)->toBe('https://explicit.com/avatar.png');
});

test('user uses notifiable trait', function () {
    $user = User::factory()->create();

    expect(method_exists($user, 'notify'))->toBeTrue()
        ->and(method_exists($user, 'routeNotificationFor'))->toBeTrue();
});

test('user uses has factory trait', function () {
    expect(method_exists(User::class, 'factory'))->toBeTrue();
});

test('user extends authenticatable', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf('Illuminate\Foundation\Auth\User');
});

test('user can be retrieved by email', function () {
    $user = User::factory()->create(['email' => 'findable@example.com']);

    $found = User::where('email', 'findable@example.com')->first();

    expect($found->id)->toBe($user->id);
});

test('user can be retrieved by workos_id', function () {
    $user = User::factory()->create(['workos_id' => 'findable-workos-id']);

    $found = User::where('workos_id', 'findable-workos-id')->first();

    expect($found->id)->toBe($user->id);
});

test('avatar can be empty string', function () {
    $user = User::factory()->create(['avatar' => '']);

    expect($user->fresh()->avatar)->toBe('');
});

test('avatar can be a url', function () {
    $user = User::factory()->create(['avatar' => 'https://example.com/avatar.jpg']);

    expect($user->fresh()->avatar)->toBe('https://example.com/avatar.jpg');
});

test('remember_token can be null', function () {
    $user = User::factory()->create(['remember_token' => null]);

    expect($user->fresh()->remember_token)->toBeNull();
});

test('remember_token can be set', function () {
    $user = User::factory()->create(['remember_token' => 'test-token']);

    expect($user->fresh()->remember_token)->toBe('test-token');
});
