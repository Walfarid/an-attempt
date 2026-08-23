<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Store a contact message and notify the owner.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::create([
            ...$request->safe()->except(['website', 'turnstile_token']),
            'ip_address' => $request->ip(),
        ]);

        Mail::to(config('contact.notification_email'))
            ->queue(new ContactMessageReceived($message));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Thanks for reaching out — I will get back to you soon.'),
        ]);

        return back();
    }
}
