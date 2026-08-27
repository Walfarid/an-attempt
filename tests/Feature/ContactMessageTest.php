<?php

use App\Models\ContactMessage;
use Carbon\CarbonImmutable;

test('factory creates valid contact message with defaults', function () {
    $message = ContactMessage::factory()->create();

    expect($message->name)->toBeString()
        ->and($message->email)->toBeString()
        ->and($message->body)->toBeString()
        ->and($message->ip_address)->toBeString()
        ->and($message->read_at)->toBeNull();

    // Subject can be string or null (factory has 70% chance of being set)
    expect($message->subject === null || is_string($message->subject))->toBeTrue();
});

test('factory read state creates message that has been read', function () {
    $message = ContactMessage::factory()->read()->create();

    expect($message->read_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('read_at is cast to a datetime when set', function () {
    $message = ContactMessage::factory()->create([
        'read_at' => '2024-06-15 10:30:00',
    ]);

    expect($message->read_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($message->read_at->year)->toBe(2024)
        ->and($message->read_at->month)->toBe(6)
        ->and($message->read_at->day)->toBe(15)
        ->and($message->read_at->hour)->toBe(10)
        ->and($message->read_at->minute)->toBe(30);
});

test('read_at remains null when not set', function () {
    $message = ContactMessage::factory()->create();

    expect($message->read_at)->toBeNull();
});

test('fillable attributes can be mass assigned', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Inquiry',
        'body' => 'This is a test message.',
        'ip_address' => '192.168.1.1',
        'read_at' => now(),
    ];

    $message = ContactMessage::create($data);

    expect($message->name)->toBe('John Doe')
        ->and($message->email)->toBe('john@example.com')
        ->and($message->subject)->toBe('Inquiry')
        ->and($message->body)->toBe('This is a test message.')
        ->and($message->ip_address)->toBe('192.168.1.1')
        ->and($message->read_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('subject can be null', function () {
    $message = ContactMessage::factory()->create([
        'subject' => null,
    ]);

    expect($message->subject)->toBeNull();
});

test('ip address is preserved', function () {
    $message = ContactMessage::factory()->create([
        'ip_address' => '203.0.113.42',
    ]);

    expect($message->ip_address)->toBe('203.0.113.42');
});
