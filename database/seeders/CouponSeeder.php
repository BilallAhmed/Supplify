<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => CouponType::Percent,
                'value' => 10,
                'min_subtotal_cents' => 2500,
                'max_uses' => null,
                'used_count' => 0,
                'starts_at' => null,
                'ends_at' => null,
                'is_active' => true,
            ]
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'SAVE5'],
            [
                'type' => CouponType::Fixed,
                'value' => 500,
                'min_subtotal_cents' => null,
                'max_uses' => 100,
                'used_count' => 0,
                'starts_at' => null,
                'ends_at' => null,
                'is_active' => true,
            ]
        );
    }
}
