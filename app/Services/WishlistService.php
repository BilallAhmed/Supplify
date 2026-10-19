<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;

class WishlistService
{
    public const SESSION_KEY = 'wishlist_product_ids';

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        if ($user = Auth::user()) {
            return WishlistItem::query()
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return array_values(array_map('intval', session(self::SESSION_KEY, [])));
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    /**
     * @return array{saved: bool, ids: list<int>}
     */
    public function toggle(Product $product): array
    {
        if ($user = Auth::user()) {
            $existing = WishlistItem::query()
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $saved = false;
            } else {
                WishlistItem::query()->create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                ]);
                $saved = true;
            }

            return ['saved' => $saved, 'ids' => $this->ids()];
        }

        $ids = $this->ids();
        if (in_array($product->id, $ids, true)) {
            $ids = array_values(array_filter($ids, fn (int $id) => $id !== $product->id));
            $saved = false;
        } else {
            $ids[] = $product->id;
            $saved = true;
        }

        session([self::SESSION_KEY => $ids]);

        return ['saved' => $saved, 'ids' => $ids];
    }

    public function mergeForUser(User $user): void
    {
        $guestIds = array_values(array_map('intval', session(self::SESSION_KEY, [])));

        if ($guestIds === []) {
            return;
        }

        $validIds = Product::query()
            ->published()
            ->whereIn('id', $guestIds)
            ->pluck('id')
            ->all();

        foreach ($validIds as $productId) {
            WishlistItem::query()->firstOrCreate([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);
        }

        session()->forget(self::SESSION_KEY);
    }
}
