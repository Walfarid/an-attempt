<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    |
    | Where notifications about new contact messages are delivered, and the
    | Cloudflare Turnstile keys protecting the public form. The site key is
    | exposed to the frontend widget; the secret stays server-side. When the
    | secret is empty (local development) captcha verification is skipped.
    |
    */

    'notification_email' => env('CONTACT_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

    'turnstile_site_key' => env('TURNSTILE_SITE_KEY'),

    'turnstile_secret_key' => env('TURNSTILE_SECRET_KEY'),

];
