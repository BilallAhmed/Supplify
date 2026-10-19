<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeds storefront categories only.
 *
 * Demo/dummy product SKUs were retired — real catalog products are imported via:
 *   php artisan catalog:import-real-products
 *
 * Re-running this seeder must not restore the old Omega Complex / Magnesium Night
 * placeholder catalog.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'energy-focus',
                'name' => 'Energy & Focus',
                'description' => 'Daytime performance support.',
                'icon_key' => 'energy',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'sleep-recovery',
                'name' => 'Sleep & Recovery',
                'description' => 'Evening wind-down essentials.',
                'icon_key' => 'sleep',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'immunity-defense',
                'name' => 'Immunity & Defense',
                'description' => 'Everyday immune support formulas.',
                'icon_key' => 'shield',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'gut-digestion',
                'name' => 'Gut & Digestion',
                'description' => 'Digestive comfort and microbiome care.',
                'icon_key' => 'gut',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
