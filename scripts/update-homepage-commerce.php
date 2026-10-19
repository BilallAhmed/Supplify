<?php

/**
 * Upsert homepage CMS labels/sections for commerce homepage updates.
 * Run: php scripts/update-homepage-commerce.php
 */

use App\Models\HomepageSection;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$featured = HomepageSection::query()->where('key', 'featured_products')->first();
if ($featured) {
    $featured->forceFill([
        'cta_label' => 'View All Products',
        'cta_url' => $featured->cta_url ?: '/shop',
    ])->save();
    echo "Updated featured_products CTA\n";
}

HomepageSection::query()->updateOrCreate(
    ['key' => 'explore_more'],
    [
        'title' => 'Explore More Formulas',
        'subtitle' => 'From the catalog',
        'body' => 'More published products from the Supplify catalog, ordered by publication date.',
        'cta_label' => 'View All Products',
        'cta_url' => '/shop',
        'config' => ['limit' => 4],
        'is_enabled' => true,
        'sort_order' => 45,
    ]
);

echo "Upserted explore_more section\n";
echo "Done.\n";
