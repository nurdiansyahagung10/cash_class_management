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

    /*
     * OpenAI-compatible chat endpoint for the class finance assistant (e.g. a 9router instance).
     * FINANCE_AI_BASE_URL is the API root that serves /chat/completions, such as http://localhost:20128/v1.
     */
    'finance_assistant' => [
        'base_url' => env('FINANCE_AI_BASE_URL'),
        'api_key' => env('FINANCE_AI_API_KEY'),
        'model' => env('FINANCE_AI_MODEL'),
        'timeout' => (int) env('FINANCE_AI_TIMEOUT', 60),
    ],

    'notion' => [
        'token' => env('NOTION_TOKEN'),
        'ledger_database_id' => env('NOTION_LEDGER_DATABASE_ID'),
        'version' => env('NOTION_VERSION', '2022-06-28'),
    ],

];
