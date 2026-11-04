<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Localisation
    |--------------------------------------------------------------------------
    | Albanian is the default language, English is fully translated and the
    | structure is Serbian-ready (reference data has nullable name_sr columns).
    */

    'supported_locales' => array_values(array_filter(explode(',', (string) env('APP_SUPPORTED_LOCALES', 'sq,en')))),

    'locale_labels' => [
        'sq' => 'Shqip',
        'en' => 'English',
        'sr' => 'Srpski',
    ],

    // Dates are stored in UTC and displayed in Kosovo time.
    'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Europe/Belgrade'),
    'date_format' => 'd.m.Y',
    'datetime_format' => 'd.m.Y H:i',

    /*
    |--------------------------------------------------------------------------
    | Sessions
    |--------------------------------------------------------------------------
    | Idle timeout = SESSION_LIFETIME minutes. A warning modal with an
    | "extend" button appears this many seconds before the session expires.
    */

    'idle_warning_seconds' => (int) env('SESSION_IDLE_WARNING_SECONDS', 120),

    /*
    |--------------------------------------------------------------------------
    | Privacy
    |--------------------------------------------------------------------------
    */

    // Public aggregate cells below this value are suppressed ("<5").
    'suppression_threshold' => 5,

    // Key for the HMAC blind index that makes encrypted party names searchable.
    'blind_index_key' => env('BLIND_INDEX_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    */

    'attachments' => [
        'disk' => env('ATTACHMENTS_DISK', env('FILESYSTEM_DISK', 'local')),
        'max_kb' => 20 * 1024,
        'mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
        'signed_url_minutes' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Researcher API
    |--------------------------------------------------------------------------
    */

    'api' => [
        'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
        'max_page_size' => 100,
    ],

    'data_use_agreement_version' => '2026-1',

    /*
    |--------------------------------------------------------------------------
    | Demo data
    |--------------------------------------------------------------------------
    | Demo users and fake records are only seeded outside production.
    */

    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', false),
    'demo_password' => env('DEMO_PASSWORD', 'password'),
    'demo_cases' => (int) env('DEMO_CASES', 200),

];
