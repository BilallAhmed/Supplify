<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hard kill switch
    |--------------------------------------------------------------------------
    |
    | When true, no third-party tracking snippets are rendered on the storefront
    | regardless of admin settings. Useful for emergency disable.
    |
    */
    'force_disable' => (bool) env('TRACKING_FORCE_DISABLE', false),

    /*
    |--------------------------------------------------------------------------
    | Default environments that may receive tracking
    |--------------------------------------------------------------------------
    |
    | Comma-separated list used as the initial default when no admin override
    | is saved. Typical value: production. Staging/local previews stay quiet
    | unless an administrator explicitly enables those environments.
    |
    */
    'default_environments' => array_values(array_filter(array_map(
        static fn (string $env): string => strtolower(trim($env)),
        explode(',', (string) env('TRACKING_ENVIRONMENTS', 'production'))
    ))),

    'consent_cookie' => 'supplify_consent',

    'consent_cookie_days' => (int) env('TRACKING_CONSENT_DAYS', 365),

    'allowed_environments' => ['production', 'staging', 'local'],

    'placements' => ['head', 'body_start', 'body_end'],

    'max_custom_snippets' => 20,

    'max_snippet_bytes' => 50000,
];
