<?php

return [
    'serper' => [
        'api_key' => env('SERPER_API_KEY', ''),
        'daily_limit' => env('SERPER_DAILY_LIMIT', 250),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    'tracking' => [
        'auto_verify_threshold' => env('TRACKING_AUTO_VERIFY_THRESHOLD', 0.8),
        'needs_audit_threshold' => env('TRACKING_NEEDS_AUDIT_THRESHOLD', 0.5),
    ],
];
