<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'random_org' => [
        'api_key' => env('RANDOM_ORG_API_KEY'),
        'endpoint' => env('RANDOM_ORG_ENDPOINT', 'https://api.random.org/json-rpc/4/invoke'),
        'timeout' => env('RANDOM_ORG_TIMEOUT', 3),
        // MVP policy: if RANDOM.ORG is unavailable, keep rolls server-authoritative
        // by falling back to PHP random_int(). Set to "fail" to reject rolls instead.
        'fallback' => env('RANDOM_ORG_FALLBACK', 'local'),
    ],

];
