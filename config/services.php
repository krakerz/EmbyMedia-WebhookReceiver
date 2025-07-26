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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tvdb' => [
        'api_key' => env('TVDB_API_KEY'),
        'api_url' => env('TVDB_API_URL', 'https://api4.thetvdb.com/v4'),
    ],

    'imdb' => [
        'api_key' => env('IMDB_API_KEY'),
        'api_url' => env('IMDB_API_URL', 'https://api.themoviedb.org/3'),
    ],

    'webhook' => [
        'refresh_timer' => env('WEBHOOK_REFRESH_TIMER', 30),
        'show_raw_data' => env('SHOW_RAW_WEBHOOK_DATA', true),
        'show_file_location' => env('SHOW_FILE_LOCATION', true),
        'show_event_details' => env('SHOW_WEBHOOK_EVENT_DETAILS', true),
        'pagination_per_page' => env('WEBHOOKS_PAGINATION_PER_PAGE', 20),
    ],

    'emby' => [
        'base_url' => env('EMBY_BASE_URL'),
        'api_key' => env('EMBY_API_KEY'),
    ],

];