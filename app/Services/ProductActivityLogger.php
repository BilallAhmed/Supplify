<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductActivity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProductActivityLogger
{
    public function log(Product $product, string $action, ?array $meta = null, ?User $actor = null): ProductActivity
    {
        $user = $actor ?? Auth::user();

        return ProductActivity::query()->create([
            'product_id' => $product->id,
            'user_id' => $user instanceof User ? $user->id : null,
            'action' => $action,
            'meta' => $meta,
        ]);
    }

    public function created(Product $product): ProductActivity
    {
        return $this->log($product, ProductActivity::ACTION_CREATED);
    }

    public function updated(Product $product): ProductActivity
    {
        return $this->log($product, ProductActivity::ACTION_UPDATED);
    }

    public function featuredChanged(Product $product, bool $featured): ProductActivity
    {
        return $this->log(
            $product,
            $featured ? ProductActivity::ACTION_FEATURED : ProductActivity::ACTION_UNFEATURED
        );
    }
}
