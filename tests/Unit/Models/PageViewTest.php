<?php

use App\Models\PageView;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('fillable attributes are mass assignable', function () {
    $view = PageView::create([
        'path' => '/posts/hello',
        'title' => 'Hello World',
        'ip' => '127.0.0.1',
        'user_agent' => 'TestAgent/1.0',
        'referrer' => 'https://example.com',
        'user_id' => null,
        'viewed_at' => '2025-08-26 12:00:00',
    ]);

    expect($view->path)->toBe('/posts/hello')
        ->and($view->title)->toBe('Hello World')
        ->and($view->ip)->toBe('127.0.0.1')
        ->and($view->user_agent)->toBe('TestAgent/1.0')
        ->and($view->referrer)->toBe('https://example.com')
        ->and($view->user_id)->toBeNull();
});

test('id is not mass assignable', function () {
    $view = PageView::create([
        'path' => '/test',
        'viewed_at' => now(),
        'id' => 999,
    ]);

    expect($view->id)->not->toBe(999);
});

test('viewed_at is cast to a datetime', function () {
    $view = PageView::create([
        'path' => '/test',
        'viewed_at' => '2025-08-26 12:00:00',
    ]);

    expect($view->viewed_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($view->viewed_at->format('Y-m-d H:i:s'))->toBe('2025-08-26 12:00:00');
});

test('model has no automatic timestamps', function () {
    $view = PageView::create([
        'path' => '/test',
        'viewed_at' => now(),
    ]);

    expect($view->created_at)->toBeNull()
        ->and($view->updated_at)->toBeNull();
});

test('nullable columns accept null', function () {
    $view = PageView::create([
        'path' => '/test',
        'title' => null,
        'ip' => null,
        'user_agent' => null,
        'referrer' => null,
        'user_id' => null,
        'viewed_at' => now(),
    ]);

    expect($view->title)->toBeNull()
        ->and($view->ip)->toBeNull()
        ->and($view->user_agent)->toBeNull()
        ->and($view->referrer)->toBeNull()
        ->and($view->user_id)->toBeNull();
});

test('user_id links to user when set', function () {
    $user = User::factory()->create();

    $view = PageView::create([
        'path' => '/test',
        'user_id' => $user->id,
        'viewed_at' => now(),
    ]);

    expect($view->user_id)->toBe($user->id);
});
