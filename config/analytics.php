<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product view tracking
    |--------------------------------------------------------------------------
    |
    | Views are recorded server-side on successful public product-detail
    | responses. Unique visitors are distinct visitor_key values in the
    | selected reporting period. visitor_key is a keyed hash of the session
    | id — not an IP address, email, or other personal identifier.
    |
    | Duplicate inserts for the same visitor + product within the throttle
    | window are ignored (refresh / double-submit protection). Component
    | re-renders cannot create extra views because tracking is not client-driven.
    |
    */

    'product_views' => [
        'enabled' => (bool) env('ANALYTICS_PRODUCT_VIEWS', true),
        'dedupe_seconds' => (int) env('ANALYTICS_VIEW_DEDUPE_SECONDS', 30),
        'exclude_admins' => true,
        'exclude_bots' => true,
        'started_at_key' => 'analytics.product_views_started_at',
    ],

];
