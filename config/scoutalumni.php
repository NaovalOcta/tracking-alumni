<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Serper.dev Search API
    |--------------------------------------------------------------------------
    */
    'serper' => [
        'api_key'     => env('SERPER_API_KEY'),
        'daily_limit' => (int) env('SERPER_DAILY_LIMIT', 250),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gemini AI API
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-3.1-flash-lite-preview'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking Thresholds & Limits
    |--------------------------------------------------------------------------
    */
    'tracking' => [
        'auto_verify_threshold' => 0.8,
        'needs_audit_threshold' => 0.5,
        'batch_limit'           => 50,
        'stale_months'          => 6,
    ],
];
