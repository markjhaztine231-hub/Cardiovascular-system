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

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'deepseek/deepseek-v4-flash-0731'),
    ],

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
    ],

    'llama' => [
        'api_key' => env('LLAMA_CLOUD_API_KEY'),
    ],

    'hf' => [
        'token' => env('HF_TOKEN'),
    ],

    'paddle_ocr' => [
        // 'local' (self-hosted FastAPI, sync) or 'cloud' (paddleocr.aistudio-app.com, async).
        'driver' => env('PADDLE_OCR_DRIVER', 'local'),
        'url' => env('PADDLE_OCR_SERVICE_URL', 'http://127.0.0.1:8010'),
        'timeout' => env('PADDLE_OCR_TIMEOUT', 120),
        'cloud' => [
            'url' => env('PADDLE_OCR_CLOUD_URL', 'https://paddleocr.aistudio-app.com/api/v2/ocr/jobs'),
            'token' => env('PADDLE_OCR_CLOUD_TOKEN'),
            'model' => env('PADDLE_OCR_CLOUD_MODEL', 'PaddleOCR-VL-1.6'),
            'poll_interval' => (int) env('PADDLE_OCR_CLOUD_POLL_INTERVAL', 5),
            'max_wait' => (int) env('PADDLE_OCR_CLOUD_MAX_WAIT', 120),
        ],
    ],

];
