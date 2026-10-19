<?php

return [

    'currency' => env('STORE_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Catalog preview pricing
    |--------------------------------------------------------------------------
    | Shared placeholder regular price for client-preview imports until real
    | commercial pricing is approved. Stored via Money::toCents().
    */
    'preview_price' => env('STORE_PREVIEW_PRICE', '1000.00'),

    /*
    |--------------------------------------------------------------------------
    | Shipping
    |--------------------------------------------------------------------------
    | method: flat | weight
    | weight rates: first matching max_grams wins (sorted ascending).
    | Missing weight on a shippable item blocks weight-based quotes.
    */
    'shipping' => [
        'method' => env('STORE_SHIPPING_METHOD', 'flat'),
        'flat_rate_cents' => (int) env('STORE_SHIPPING_FLAT_CENTS', 599),
        'free_over_cents' => (int) env('STORE_SHIPPING_FREE_OVER_CENTS', 7500),
        'weight_rates' => [
            ['max_grams' => 500, 'price_cents' => (int) env('STORE_SHIPPING_W500_CENTS', 599)],
            ['max_grams' => 1000, 'price_cents' => (int) env('STORE_SHIPPING_W1000_CENTS', 799)],
            ['max_grams' => 2000, 'price_cents' => (int) env('STORE_SHIPPING_W2000_CENTS', 999)],
            ['max_grams' => 5000, 'price_cents' => (int) env('STORE_SHIPPING_W5000_CENTS', 1499)],
        ],
        'classes' => [
            'standard' => 'Standard',
            'fragile' => 'Fragile',
            'oversized' => 'Oversized',
        ],
    ],

    'tax' => [
        'rate_percent' => (float) env('STORE_TAX_RATE_PERCENT', 0),
    ],

];
