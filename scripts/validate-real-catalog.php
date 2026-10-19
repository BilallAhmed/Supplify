<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$errors = [];

$active = App\Models\Product::query()->get();
$trashedDummies = App\Models\Product::onlyTrashed()->whereIn('sku', [
    'OMG-1000', 'MAG-NITE', 'GRN-DAILY', 'VIT-D3', 'COL-REN', 'PRO-BAL',
    'ASH-CALM', 'CRE-PWR', 'ZNC-SHLD', 'BCP-FOC', 'ELC-HYD',
])->count();

echo 'active_products='.$active->count().PHP_EOL;
echo 'trashed_dummies='.$trashedDummies.PHP_EOL;

$skus = $active->pluck('sku');
$slugs = $active->pluck('slug');
if ($skus->count() !== $skus->unique()->count()) {
    $errors[] = 'Duplicate SKUs';
}
if ($slugs->count() !== $slugs->unique()->count()) {
    $errors[] = 'Duplicate slugs';
}

$price = App\Support\Money::toCents(config('store.preview_price'));
$demoCount = 0;

foreach ($active as $p) {
    $p->load(['images', 'tags']);
    $primary = $p->primaryImage();
    if (! $primary) {
        $errors[] = $p->sku.' missing featured image';
    } elseif (! $primary->is_primary) {
        $errors[] = $p->sku.' primary flag wrong';
    } elseif (! str_starts_with($primary->path, 'images/products/')) {
        $errors[] = $p->sku.' featured path unexpected: '.$primary->path;
    }

    if ($p->price_cents !== $price) {
        $errors[] = $p->sku.' price '.$p->price_cents.' != '.$price;
    }
    if ($p->isPurchasable()) {
        $errors[] = $p->sku.' still purchasable';
    }
    if (! $p->hasTemporaryPricing()) {
        $errors[] = $p->sku.' missing temporary-pricing tag';
    }
    if ($p->hasEditorialDraft()) {
        $demoCount++;
        if ($p->sku !== 'SUP-MAGNESIUM-250MG-90') {
            $errors[] = 'Unexpected editorial draft on '.$p->sku;
        }
        if (! filled($p->benefits) || ! filled($p->supplement_facts)) {
            $errors[] = 'Demo product missing expanded fields';
        }
    } else {
        if (filled($p->benefits) || filled($p->directions)) {
            $errors[] = $p->sku.' should not have expanded long-form content';
        }
    }

    $paths = $p->images->pluck('path');
    if ($paths->count() !== $paths->unique()->count()) {
        $errors[] = $p->sku.' duplicate gallery paths';
    }

    echo sprintf(
        "%s | %s | featured=%s | gallery=%d | purchasable=%s | url=%s\n",
        $p->sku,
        $p->name,
        $primary?->path,
        $p->galleryImages()->count(),
        $p->isPurchasable() ? 'Y' : 'N',
        $primary?->url()
    );
}

echo 'demo_count='.$demoCount.PHP_EOL;

$activeDummy = App\Models\Product::query()->whereIn('sku', [
    'OMG-1000', 'MAG-NITE', 'GRN-DAILY', 'VIT-D3', 'COL-REN', 'PRO-BAL',
    'ASH-CALM', 'CRE-PWR', 'ZNC-SHLD', 'BCP-FOC', 'ELC-HYD',
])->count();
if ($activeDummy > 0) {
    $errors[] = 'Dummy products still active: '.$activeDummy;
}

$hero = App\Models\HomepageSection::query()->where('key', 'hero')->first();
foreach (($hero->config['slides'] ?? []) as $i => $slide) {
    $sku = $slide['product_sku'] ?? '';
    echo "hero_slide{$i}={$sku}".PHP_EOL;
    if (in_array($sku, ['OMG-1000', 'MAG-NITE', 'GRN-DAILY'], true)) {
        $errors[] = 'Hero still references dummy SKU '.$sku;
    }
}

echo 'orders_intact='.App\Models\Order::count().PHP_EOL;

if ($errors === []) {
    echo "VALIDATION_OK\n";
    exit(0);
}

echo "VALIDATION_FAILED\n";
foreach ($errors as $e) {
    echo 'ERR: '.$e.PHP_EOL;
}
exit(1);
