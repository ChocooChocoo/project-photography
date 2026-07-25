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

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'mode' => env('STRIPE_MODE', 'test'),
    ],

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'mode' => env('PAYMONGO_MODE', 'test'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Groq (AI Assistant)
    |--------------------------------------------------------------------------
    |
    | Powers the photography-service AI assistant. The API key is server-side
    | only and must never be exposed to the browser or written to logs.
    | Budget caps sit under the model's published limits to leave headroom.
    |
    */

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'qwen/qwen3.6-27b'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'timeout' => (int) env('GROQ_TIMEOUT', 20),
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 400),
        'temperature' => (float) env('GROQ_TEMPERATURE', 0.3),

        // Reasoning models (Qwen3 family) otherwise emit their chain of thought
        // as the reply body, which burns the token budget and truncates the real
        // answer. Sent only when non-empty, so a non-reasoning model can opt out
        // by setting these to an empty string.
        'reasoning_format' => env('GROQ_REASONING_FORMAT', 'parsed'),
        'reasoning_effort' => env('GROQ_REASONING_EFFORT', 'none'),

        // Published limits: 30 RPM / 1,000 RPD / 8,000 TPM / 200,000 TPD.
        'limits' => [
            'requests_per_minute' => (int) env('GROQ_LIMIT_RPM', 25),
            'requests_per_day' => (int) env('GROQ_LIMIT_RPD', 900),
            'tokens_per_minute' => (int) env('GROQ_LIMIT_TPM', 7000),
            'tokens_per_day' => (int) env('GROQ_LIMIT_TPD', 180000),
            'requests_per_user_per_minute' => (int) env('GROQ_LIMIT_USER_RPM', 8),
        ],

        // Conversation and studio context controls (token spend management).
        'history_messages' => (int) env('GROQ_HISTORY_MESSAGES', 6),
        'history_characters' => (int) env('GROQ_HISTORY_CHARACTERS', 3000),
        'package_context_limit' => (int) env('GROQ_PACKAGE_CONTEXT_LIMIT', 10),
        'faq_context_limit' => (int) env('GROQ_FAQ_CONTEXT_LIMIT', 6),
    ],

];
