<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'products='.App\Models\Product::withTrashed()->count().PHP_EOL;
foreach (App\Models\Product::withTrashed()->get(['id', 'sku', 'name', 'deleted_at', 'is_active', 'price_cents']) as $p) {
    echo $p->id.'|'.$p->sku.'|'.$p->name.'|del='.($p->deleted_at ? 'Y' : 'N').'|active='.(int) $p->is_active.'|'.$p->price_cents.PHP_EOL;
}
echo 'img='.App\Models\ProductImage::count().PHP_EOL;
foreach (App\Models\ProductImage::limit(5)->get() as $i) {
    echo $i->path.PHP_EOL;
}
echo 'media_prod='.App\Models\MediaAsset::where('path', 'like', 'images/products/%')->count().PHP_EOL;
foreach (App\Models\MediaAsset::where('path', 'like', 'images/products/%')->limit(5)->get(['id', 'path', 'disk']) as $a) {
    echo $a->id.' '.$a->disk.' '.$a->path.PHP_EOL;
}
echo 'orders='.App\Models\Order::count().PHP_EOL;
echo 'order_items='.App\Models\OrderItem::count().PHP_EOL;
echo 'cats='.App\Models\Category::pluck('slug')->implode(',').PHP_EOL;
$hero = App\Models\HomepageSection::query()->where('key', 'hero')->first();
if ($hero) {
    foreach (($hero->config['slides'] ?? []) as $i => $slide) {
        echo 'slide'.$i.' sku='.($slide['product_sku'] ?? '').' img='.($slide['hero_image'] ?? '').PHP_EOL;
    }
}
