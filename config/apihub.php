<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP client defaults
    |--------------------------------------------------------------------------
    |
    | Applied to every driver's outbound request. Individual drivers may
    | override these where a provider needs a longer timeout (e.g. AI calls
    | that stream large completions).
    |
    */
    'http' => [
        'timeout' => (int) env('APIHUB_HTTP_TIMEOUT', 10),
        'retries' => (int) env('APIHUB_HTTP_RETRIES', 2),
        'retry_delay' => (int) env('APIHUB_HTTP_RETRY_DELAY', 250),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, each API call is logged at debug level. Any payload key
    | whose name contains one of the redact_keys needles is replaced before
    | it reaches the log channel, so credentials never get persisted.
    |
    */
    'logging' => [
        'enabled' => (bool) env('APIHUB_LOGGING', false),
        'redact_keys' => [
            'password', 'passwd', 'secret', 'token', 'api_key', 'apikey',
            'authorization', 'auth', 'cookie', 'card', 'cvv', 'pan',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Slow calls (email, SMS, AI) may be dispatched on a queue. Leave the
    | connection null to use the application default connection.
    |
    */
    'queue' => [
        'connection' => env('APIHUB_QUEUE_CONNECTION'),
        'name' => env('APIHUB_QUEUE_NAME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'default' => env('APIHUB_PAYMENTS_DRIVER', 'stripe'),
        'drivers' => [
            'stripe' => [
                'secret' => env('STRIPE_SECRET'),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            ],
            'razorpay' => [
                'key' => env('RAZORPAY_KEY'),
                'secret' => env('RAZORPAY_SECRET'),
                'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
            ],
            'paypal' => [
                'client_id' => env('PAYPAL_CLIENT_ID'),
                'client_secret' => env('PAYPAL_CLIENT_SECRET'),
                'mode' => env('PAYPAL_MODE', 'sandbox'),
                'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            ],
            'square' => [
                'access_token' => env('SQUARE_ACCESS_TOKEN'),
                'environment' => env('SQUARE_ENV', 'sandbox'),
                'version' => env('SQUARE_VERSION', '2024-10-17'),
                'signature_key' => env('SQUARE_WEBHOOK_SIGNATURE_KEY'),
                'notification_url' => env('SQUARE_WEBHOOK_URL'),
            ],
            'authorizenet' => [
                'login_id' => env('AUTHORIZENET_LOGIN_ID'),
                'transaction_key' => env('AUTHORIZENET_TRANSACTION_KEY'),
                'environment' => env('AUTHORIZENET_ENV', 'sandbox'),
                'signature_key' => env('AUTHORIZENET_SIGNATURE_KEY'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'default' => env('APIHUB_AI_DRIVER', 'openai'),
        'drivers' => [
            'openai' => [
                'api_key' => env('OPENAI_API_KEY'),
                'organization' => env('OPENAI_ORGANIZATION'),
                'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            ],
            'anthropic' => [
                'api_key' => env('ANTHROPIC_API_KEY'),
                'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
                'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            ],
            'gemini' => [
                'api_key' => env('GEMINI_API_KEY'),
                'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            ],
            'deepseek' => [
                'api_key' => env('DEEPSEEK_API_KEY'),
                'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    */
    'email' => [
        'default' => env('APIHUB_EMAIL_DRIVER', 'mailgun'),
        'drivers' => [
            'mailgun' => [
                'api_key' => env('MAILGUN_API_KEY'),
                'domain' => env('MAILGUN_DOMAIN'),
                'endpoint' => env('MAILGUN_ENDPOINT', 'https://api.mailgun.net'),
            ],
            'sendgrid' => [
                'api_key' => env('SENDGRID_API_KEY'),
            ],
            'ses' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            ],
            'resend' => [
                'api_key' => env('RESEND_API_KEY'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS & Messaging
    |--------------------------------------------------------------------------
    */
    'messaging' => [
        'default' => env('APIHUB_MESSAGING_DRIVER', 'twilio'),
        'drivers' => [
            'twilio' => [
                'sid' => env('TWILIO_SID'),
                'token' => env('TWILIO_AUTH_TOKEN'),
                'from' => env('TWILIO_FROM'),
            ],
            'vonage' => [
                'key' => env('VONAGE_KEY'),
                'secret' => env('VONAGE_SECRET'),
                'from' => env('VONAGE_FROM'),
            ],
            'msg91' => [
                'auth_key' => env('MSG91_AUTH_KEY'),
                'sender' => env('MSG91_SENDER'),
            ],
            'telegram' => [
                'bot_token' => env('TELEGRAM_BOT_TOKEN'),
            ],
            'whatsapp' => [
                'token' => env('WHATSAPP_TOKEN'),
                'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            ],
            'slack' => [
                'token' => env('SLACK_BOT_TOKEN'),
                'webhook_url' => env('SLACK_WEBHOOK_URL'),
            ],
            'discord' => [
                'bot_token' => env('DISCORD_BOT_TOKEN'),
                'webhook_url' => env('DISCORD_WEBHOOK_URL'),
            ],
        ],
    ],

];
