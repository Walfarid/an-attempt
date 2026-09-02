<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'workos' => [
        'client_id' => env('WORKOS_CLIENT_ID'),
        'secret' => env('WORKOS_API_KEY'),
        'redirect_url' => env('WORKOS_REDIRECT_URL'),
    ],

    'clarity' => [
        'id' => env('CLARITY_PROJECT_ID'),
    ],

    'google' => [
        'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    'ezoic' => [
        // Non-intrusive Ezoic ads, limited to the single-post page. When
        // disabled (default) the header scripts, the placeholder slot, and
        // the ads.txt redirect are all omitted. The placeholder ID is
        // generated in the Ezoic dashboard (Placeholders) — the number must
        // match the dashboard placement, not an arbitrary value.
        'enabled' => env('EZOIC_ENABLED', false),
        'placeholder_id' => env('EZOIC_PLACEHOLDER_ID'),
        'adstxt_manager_id' => env('EZOIC_ADSTXT_MANAGER_ID', '19390'),
    ],

];
