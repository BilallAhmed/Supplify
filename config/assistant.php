<?php

/**
 * Supplify Assistant (Module 14) — website-only shopping helper.
 *
 * No external AI API, local LLM, embeddings, or model training.
 * Public storefront widget stays disabled until later acceptance layers pass.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Public widget
    |--------------------------------------------------------------------------
    |
    | Hard default off. Runtime may also store assistant.enabled in store_settings;
    | both must allow the widget before it can render (Layer 5+).
    |
    */
    'enabled' => (bool) env('ASSISTANT_ENABLED', false),

    'public_name' => 'Supplify Assistant',

    'tagline' => 'Finds products and answers using Supplify’s published website information.',

    /*
    |--------------------------------------------------------------------------
    | Operating limits
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'message_max_length' => 500,
        'max_session_turns' => 20,
        'max_products_per_answer' => 3,
        'max_articles_per_answer' => 2,
        'max_pages_per_answer' => 2,
        'rate_limit_per_minute' => 20,
        'session_idle_minutes' => 60,
        'excerpt_max_chars' => 400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fields the assistant may read (after eligibility + approval)
    |--------------------------------------------------------------------------
    |
    | Live price/stock/purchasability are re-read at card render time (Layer 4).
    | Never invent values; never treat photos as ingredient evidence.
    |
    */
    'readable_fields' => [
        'product' => [
            'name',
            'slug',
            'sku',
            'short_description',
            'ingredients',
            'supplement_facts',
            'directions',
            'warnings',
            'categories.name',
            'categories.slug',
            'tags.name',
            'tags.slug', // excluding internal control tags in eligibility
            'price_cents', // live only when rendering cards
            'sale_price_cents',
            'stock_status',
            'stock_quantity',
            'product_type',
            'variations.sku',
            'variations.price_cents',
            'variations.stock_status',
            'variations.stock_quantity',
            'variations.option_labels',
        ],
        'post' => [
            'title',
            'slug',
            'excerpt',
            'body',
            'category.name',
            'tags.name',
            'published_at',
        ],
        'page' => [
            'title',
            'slug',
            'body',
            'sections', // structured CMS sections only when published
            'published_at',
        ],
        'category' => [
            'name',
            'slug',
            'description',
        ],
        'faq' => [
            'question',
            'answer',
            'intent',
            'aliases',
        ],
        'alias' => [
            'phrase',
            'normalized_phrase',
            'locale',
            'maps_to_intent',
            'target_type',
            'target_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prohibited / never exposed via assistant answers
    |--------------------------------------------------------------------------
    */
    'prohibited_fields' => [
        'orders.*',
        'users.*',
        'addresses.*',
        'carts.*',
        'payment.*',
        'product.cost',
        'product.internal_notes',
        'product.seo_title', // marketing SEO drafts are not product facts
        'product.seo_description',
        'product.weight_grams', // fulfillment detail, not shopping Q&A
        'product.shipping_class',
        'product.dimensions',
        'post.seo_title',
        'post.seo_description',
        'page.seo_title',
        'page.seo_description',
        'any.unpublished_draft_body',
        'any.editorial_draft_claims',
        'any.inferred_from_images',
        'any.customer_message_as_knowledge',
    ],

    /*
    |--------------------------------------------------------------------------
    | Internal tags that block or alter eligibility
    |--------------------------------------------------------------------------
    */
    'control_tags' => [
        'editorial_draft' => 'editorial-draft',
        'temporary_pricing' => 'temporary-pricing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy page slugs used for delivery / returns / contact intents
    |--------------------------------------------------------------------------
    */
    'policy_page_slugs' => [
        'shipping',
        'returns',
        'faq',
        'contact',
        'privacy',
        'terms',
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge synchronization (Layer 2)
    |--------------------------------------------------------------------------
    |
    | Requires a queue worker in production for RefreshAssistantDocumentJob.
    | Reconciliation runs hourly via the scheduler (php artisan schedule:work).
    | Rebuild: php artisan assistant:rebuild-knowledge [--sync]
    |
    */
    'sync' => [
        'reconcile_schedule' => 'hourly',
        'live_fallback_multiplier' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Search & matching (Layer 3)
    |--------------------------------------------------------------------------
    |
    | driver: auto (MySQL FULLTEXT when indexed, else portable tokens),
    |         portable (LIKE/token only — always used in SQLite tests),
    |         fulltext (prefer MySQL FULLTEXT with portable fallback).
    | No vector database. Aliases are reviewed dictionary rows, not ML.
    |
    */
    'search' => [
        'driver' => env('ASSISTANT_SEARCH_DRIVER', 'auto'),
        'min_hit_score' => 40,
        'confidence' => [
            'high' => 80,
            'medium' => 55,
            'clarify_score_gap' => 12,
        ],
        'ingredient_cues' => [
            'ingredient',
            'ingredients',
            'formula',
            'supplement facts',
            'vitamin',
            'magnesium',
            'zinc',
            'omega',
        ],
        // Explicit reviewed goal phrases → matching approved categories by slug/name.
        // Do not treat these as medical benefit claims.
        'wellness_goals' => [
            'sleep' => ['sleep', 'neend', 'nind', 'نیند', 'recovery night'],
            'energy' => ['energy', 'energy focus', 'focus', 'energy boost', 'توانائی'],
            'immunity' => ['immunity', 'immune', 'defense', 'مدافعت'],
            'gut' => ['gut', 'digestion', 'digestive', 'ہاضمہ'],
        ],
        'intent_phrases' => [
            'find_product' => ['find', 'looking for', 'product', 'buy', 'show me', 'mujhe chahiye', 'مجھے چاہیے'],
            'price_or_availability' => ['price', 'cost', 'kitne ka', 'in stock', 'available', 'قیمت', 'stock'],
            'ingredients_or_directions' => ['ingredient', 'ingredients', 'directions', 'how to use', 'supplement facts', 'dose label'],
            'related_articles' => ['read', 'article', 'blog', 'read about', 'learn', 'مقالہ'],
            'delivery_returns_contact' => ['shipping', 'delivery', 'returns', 'refund', 'contact', 'customer support', 'support team', 'store support', 'ڈیلیوری', 'واپسی'],
            'boundary' => [
                'diagnose',
                'diagnosis',
                'prescribe',
                'prescription',
                'can i take',
                'should i take',
                'safe for',
                'breastfeeding',
                'nursing',
                'حاملہ',
                'حمل',
                'بچوں',
                'دوائی کے ساتھ',
                'pregnant',
                'pregnancy',
                'for my child',
                'medication interaction',
                'cure my',
                'treat my',
            ],
        ],
    ],
];
