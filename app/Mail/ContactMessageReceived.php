<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public ContactMessage $message)
    {
        //
    }

    /**
     * Get the message envelope.
     *
     * Reply-to points at the sender so the owner can answer directly.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('New contact message: :subject', [
                'subject' => $this->message->subject ?? __('(no subject)'),
            ]),
            replyTo: [$this->message->email],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            text: 'mail.contact-message-received',
        );
    }
}
