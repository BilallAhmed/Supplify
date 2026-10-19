<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'featured' => 'featured_products',
            'categories' => 'shop_by_goal',
        ] as $from => $to) {
            $legacy = DB::table('homepage_sections')->where('key', $from)->first();
            $canonical = DB::table('homepage_sections')->where('key', $to)->first();

            if ($legacy && ! $canonical) {
                DB::table('homepage_sections')->where('id', $legacy->id)->update(['key' => $to]);
            } elseif ($legacy && $canonical) {
                DB::table('homepage_sections')->where('id', $legacy->id)->update([
                    'is_enabled' => false,
                    'sort_order' => 999,
                ]);
            }
        }

        if (! DB::table('homepage_sections')->where('key', 'promo')->exists()) {
            DB::table('homepage_sections')->insert([
                'key' => 'promo',
                'title' => null,
                'subtitle' => null,
                'body' => null,
                'cta_label' => null,
                'cta_url' => null,
                'image_path' => null,
                'config' => json_encode(['starts_at' => null, 'ends_at' => null]),
                'is_enabled' => false,
                'sort_order' => 70,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $orders = [
            'hero' => 10,
            'trust' => 20,
            'shop_by_goal' => 30,
            'featured_products' => 40,
            'story' => 50,
            'education' => 60,
            'promo' => 70,
            'newsletter' => 80,
        ];

        foreach ($orders as $key => $sort) {
            DB::table('homepage_sections')->where('key', $key)->update(['sort_order' => $sort]);
        }

        DB::table('homepage_sections')->whereIn('key', ['featured', 'categories'])->update([
            'is_enabled' => false,
            'sort_order' => 999,
        ]);

        DB::table('homepage_sections')->where('key', 'testimonials')->update([
            'is_enabled' => false,
            'sort_order' => 990,
        ]);

        $story = DB::table('homepage_sections')->where('key', 'story')->first();
        if ($story) {
            DB::table('homepage_sections')->where('key', 'story')->update([
                'title' => 'Our journey',
                'subtitle' => 'From source to doorstep',
                'body' => 'A short look at how we move from ingredient selection to the bottle you receive.',
                'cta_label' => 'Discover Our Approach',
                'cta_url' => '/pages/about',
                'config' => json_encode([
                    'beats' => [
                        [
                            'label' => '01',
                            'title' => 'Ingredient selection',
                            'body' => 'We evaluate ingredients for clarity of purpose and fit within a daily routine.',
                            'icon' => 'leaf',
                        ],
                        [
                            'label' => '02',
                            'title' => 'Formulation',
                            'body' => 'Formulas are drafted for readable labels and practical serving sizes.',
                            'icon' => 'flask',
                        ],
                        [
                            'label' => '03',
                            'title' => 'Manufacturing & checks',
                            'body' => 'Production partners follow agreed quality checks before products ship.',
                            'icon' => 'shield',
                        ],
                        [
                            'label' => '04',
                            'title' => 'Packaging & delivery',
                            'body' => 'Finished goods are packed for US-focused fulfillment and careful transit.',
                            'icon' => 'package',
                        ],
                    ],
                ], JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }

        DB::table('categories')->where('slug', 'energy-focus')->update(['icon_key' => 'energy']);
        DB::table('categories')->where('slug', 'sleep-recovery')->update(['icon_key' => 'sleep']);
        DB::table('categories')->where('slug', 'immunity-defense')->update(['icon_key' => 'shield']);
        DB::table('categories')->where('slug', 'gut-digestion')->update(['icon_key' => 'gut']);
    }

    public function down(): void
    {
        // Non-destructive: leave content as-is.
    }
};
