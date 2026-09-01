<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    |
    | The email address visitors reach via the "Send email" CTA on the home
    | page. Defaults to MAIL_FROM_ADDRESS when CONTACT_NOTIFICATION_EMAIL is
    | not set in the environment.
    |
    */

    'notification_email' => env('CONTACT_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

];
