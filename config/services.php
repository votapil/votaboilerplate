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

    /*
    |--------------------------------------------------------------------------
    | Telegram Error Monitoring Bot
    |--------------------------------------------------------------------------
    |
    | Backs the 'telegram' log channel in config/logging.php. Keep it a bot of
    | its own rather than the product's bot: error traffic must never share a
    | rate-limit budget with messages users are waiting for.
    |
    | Both values empty (the default) makes the channel a no-op, which is what
    | local and CI runs want.
    |
    */
    'telegram_error_bot' => [
        'token' => env('TELEGRAM_ERROR_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_ERROR_CHAT_ID'),
    ],

];
