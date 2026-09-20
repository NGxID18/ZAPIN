<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZAPIN Application Specific Configurations
    |--------------------------------------------------------------------------
    */

    'google_sheet_url' => env('GOOGLE_SHEET_URL', 'https://docs.google.com/spreadsheets'),

    'sheet_webhook_url' => env('GOOGLE_SHEET_WEBHOOK_URL', ''),

    'api_key' => env('ZAPIN_API_KEY'),

    'ews_email' => env('EWS_NOTIFICATION_EMAIL', 'kepala.elektromedis@rsjko.local'),

    'default_password' => env('AUTH_DEFAULT_PASSWORD', '1234'),
];

