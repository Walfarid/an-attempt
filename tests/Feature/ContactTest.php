<?php

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

function validContactPayload(array $overrides = []): array
{
    return [
        'name' => 'Jane Visitor',
        'email' => 'jane@example.com',
        'subject' => 'Hello there',
        'body' => 'I would like to work with you.',
        ...$overrides,
    ];
}

test('guests can submit a contact message', function () {
    Mail::fake();

    $this->post('/contact', validContactPayload())
        ->assertRedirect('/');

    $message = ContactMessage::where('email', 'jane@example.com')->first();

    expect($message)->not->toBeNull()
        ->and($message?->name)->toBe('Jane Visitor')
        ->and($message?->read_at)->toBeNull()
        ->and($message?->ip_address)->not->toBeNull();

    Mail::assertQueued(ContactMessageReceived::class, function (ContactMessageReceived $mail) {
        return $mail->hasTo(config('contact.notification_email'))
            && $mail->message->email === 'jane@example.com';
    });
});

test('contact messages require valid data', function () {
    Mail::fake();

    $this->post('/contact', [
        'name' => '',
        'email' => 'not-an-email',
        'body' => '',
    ])->assertInvalid(['name', 'email', 'body']);

    expect(ContactMessage::count())->toBe(0);
});

test('the honeypot field rejects bots', function () {
    Mail::fake();

    $this->post('/contact', validContactPayload([
        'website' => 'http://spam.example',
    ]))->assertInvalid('website');

    expect(ContactMessage::count())->toBe(0);
    Mail::assertNothingQueued();
});

test('the turnstile token is verified against cloudflare', function () {
    config(['contact.turnstile_secret_key' => 'test-secret']);
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false]),
    ]);

    $this->post('/contact', validContactPayload([
        'cf-turnstile-response' => 'bogus-token',
    ]))->assertInvalid('turnstile_token');

    Http::assertSent(fn ($request) => $request['secret'] === 'test-secret'
        && $request['response'] === 'bogus-token');
});

test('a successful turnstile verification passes', function () {
    config(['contact.turnstile_secret_key' => 'test-secret']);
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);
    Mail::fake();

    $this->post('/contact', validContactPayload([
        'cf-turnstile-response' => 'good-token',
    ]))->assertRedirect('/');

    expect(ContactMessage::count())->toBe(1);
});

test('submission is rate limited per ip', function () {
    Mail::fake();

    foreach (range(1, 5) as $i) {
        $this->post('/contact', validContactPayload([
            'email' => "visitor-{$i}@example.com",
        ]))->assertRedirect('/');
    }

    $this->post('/contact', validContactPayload([
        'email' => 'one-too-many@example.com',
    ]))->assertStatus(429);

    expect(ContactMessage::count())->toBe(5);
});
