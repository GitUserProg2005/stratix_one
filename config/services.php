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

    'transcribe' => [
        'url' => env('TRANSCRIBE_SERVICE_URL', 'http://host.docker.internal:8001'),
    ],

    'audio_analysis' => [
        'url' => env('AUDIO_ANALYSIS_SERVICE_URL', 'http://host.docker.internal:8002'),
        'file_field' => env('AUDIO_ANALYSIS_FILE_FIELD', 'file'),
    ],

    'nominatim' => [
        'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
        'user_agent' => env('NOMINATIM_USER_AGENT', 'DriveeApp/1.0'),
    ],

    'osrm' => [
        'url' => env('OSRM_URL', 'http://localhost:5000')
    ],

    'map' => [
        'tileserver_url' => env('TILESERVER_URL', 'http://localhost:8082'),
        'osrm_url' => env('OSRM_URL', 'http://localhost:5000'),
        'tileserver_style' => env('TILESERVER_STYLE', 'basic-preview'),
    ],

    'whisper' => [
        'url' => env('WHISPER_URL', 'http://localhost:9001'),
        'model' => env('WHISPER_MODEL', 'base'),
        'language' => env('WHISPER_LANGUAGE', 'ru'),
        'timeout' => env('WHISPER_TIMEOUT', 120),
    ],

    'mistral' => [
        'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
        'timeout' => env('MISTRAL_TIMEOUT', 120),
    ],

];
