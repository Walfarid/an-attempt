<?php

use App\Models\Click;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('fillable attributes are mass assignable', function () {
    $click = Click::create([
        'path' => '/posts/hello',
        'element' => 'a.read-more',
        'label' => 'Read more',
        'ip' => '127.0.0.1',
        'user_agent' => 'TestAgent/1.0',
        'user_id' => null,
        'clicked_at' => '2025-08-26 12:00:00',
    ]);

    expect($click->path)->toBe('/posts/hello')
        ->and($click->element)->toBe('a.read-more')
        ->and($click->label)->toBe('Read more')
        ->and($click->ip)->toBe('127.0.0.1')
        ->and($click->user_agent)->toBe('TestAgent/1.0')
        ->and($click->user_id)->toBeNull();
});

test('id is not mass assignable', function () {
    $click = Click::create([
        'path' => '/test',
        'clicked_at' => now(),
        'id' => 999,
    ]);

    expect($click->id)->not->toBe(999);
});

test('clicked_at is cast to a datetime', function () {
    $click = Click::create([
        'path' => '/test',
        'clicked_at' => '2025-08-26 12:00:00',
    ]);

    expect($click->clicked_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($click->clicked_at->format('Y-m-d H:i:s'))->toBe('2025-08-26 12:00:00');
});

test('model has no automatic timestamps', function () {
    $click = Click::create([
        'path' => '/test',
        'clicked_at' => now(),
    ]);

    expect($click->created_at)->toBeNull()
        ->and($click->updated_at)->toBeNull();
});

test('nullable columns accept null', function () {
    $click = Click::create([
        'path' => '/test',
        'element' => null,
        'label' => null,
        'ip' => null,
        'user_agent' => null,
        'user_id' => null,
        'clicked_at' => now(),
    ]);

    expect($click->element)->toBeNull()
        ->and($click->label)->toBeNull()
        ->and($click->ip)->toBeNull()
        ->and($click->user_agent)->toBeNull()
        ->and($click->user_id)->toBeNull();
});

test('user_id links to user when set', function () {
    $user = User::factory()->create();

    $click = Click::create([
        'path' => '/test',
        'user_id' => $user->id,
        'clicked_at' => now(),
    ]);

    expect($click->user_id)->toBe($user->id);
});
