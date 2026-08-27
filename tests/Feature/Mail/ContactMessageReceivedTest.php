<?php

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;

test('mailable envelope has correct subject with contact message subject', function () {
    $contactMessage = ContactMessage::factory()->make([
        'subject' => 'Inquiry about services',
    ]);

    $mailable = new ContactMessageReceived($contactMessage);
    $envelope = $mailable->envelope();

    expect($envelope)->toBeInstanceOf(Envelope::class)
        ->and($envelope->subject)->toBe('New contact message: Inquiry about services');
});

test('mailable envelope falls back to no subject placeholder when subject is null', function () {
    $contactMessage = ContactMessage::factory()->make([
        'subject' => null,
    ]);

    $mailable = new ContactMessageReceived($contactMessage);
    $envelope = $mailable->envelope();

    expect($envelope->subject)->toBe('New contact message: (no subject)');
});

test('mailable envelope has reply-to set to sender email', function () {
    $contactMessage = ContactMessage::factory()->make([
        'email' => 'sender@example.com',
    ]);

    $mailable = new ContactMessageReceived($contactMessage);
    $envelope = $mailable->envelope();

    expect($envelope->replyTo)->toHaveCount(1)
        ->and($envelope->replyTo[0]->address)->toBe('sender@example.com');
});

test('mailable content uses the correct text view', function () {
    $contactMessage = ContactMessage::factory()->make();
    $mailable = new ContactMessageReceived($contactMessage);

    $content = $mailable->content();

    expect($content)->toBeInstanceOf(Content::class)
        ->and($content->view)->toBeNull()
        ->and($content->text)->toBe('mail.contact-message-received');
});

test('mailable can be sent via mail fake', function () {
    Mail::fake();

    $contactMessage = ContactMessage::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Test subject',
        'body' => 'Test body',
    ]);

    Mail::to('admin@example.com')->send(new ContactMessageReceived($contactMessage));

    Mail::assertSent(ContactMessageReceived::class, function ($mail) use ($contactMessage) {
        return $mail->message->is($contactMessage);
    });
});

test('mailable has the contact message accessible via public property', function () {
    $contactMessage = ContactMessage::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Test subject',
        'body' => 'This is a test message body.',
    ]);

    $mailable = new ContactMessageReceived($contactMessage);

    // The ContactMessage is accessible via the public $message property
    expect($mailable->message->is($contactMessage))->toBeTrue()
        ->and($mailable->message->name)->toBe('Jane Doe')
        ->and($mailable->message->email)->toBe('jane@example.com')
        ->and($mailable->message->subject)->toBe('Test subject')
        ->and($mailable->message->body)->toBe('This is a test message body.');
});

test('mailable uses queueable trait', function () {
    $contactMessage = ContactMessage::factory()->make();
    $mailable = new ContactMessageReceived($contactMessage);

    // Verify the mailable can be queued (has the necessary trait methods)
    expect(method_exists($mailable, 'onQueue'))->toBeTrue();
});

test('mailable uses serializes models trait', function () {
    $contactMessage = ContactMessage::factory()->create([
        'name' => 'Serialized User',
        'email' => 'serialized@example.com',
    ]);
    $mailable = new ContactMessageReceived($contactMessage);

    // The SerializesModels trait allows proper serialization of the model
    $serialized = serialize($mailable);
    $unserialized = unserialize($serialized);

    expect($unserialized)->toBeInstanceOf(ContactMessageReceived::class)
        ->and($unserialized->message->name)->toBe($contactMessage->name)
        ->and($unserialized->message->email)->toBe($contactMessage->email);
});
