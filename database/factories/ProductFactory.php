<?php

namespace Database\Factories;

use App\Enums\StockStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $qty = fake()->numberBetween(0, 100);
        $threshold = 5;

        $status = match (true) {
            $qty <= 0 => StockStatus::OutOfStock,
            $qty <= $threshold => StockStatus::LowStock,
            default => StockStatus::InStock,
        };

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'sku' => 'SKU-'.fake()->unique()->bothify('??###'),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'benefits' => fake()->sentence(),
            'ingredients' => fake()->sentence(),
            'supplement_facts' => "Serving Size: 1 capsule\nVitamin D3 25 mcg",
            'directions' => 'Take one capsule daily with food.',
            'warnings' => 'Consult a physician before use.',
            'price_cents' => fake()->numberBetween(1500, 8000),
            'sale_price_cents' => null,
            'stock_quantity' => $qty,
            'stock_status' => $status,
            'low_stock_threshold' => $threshold,
            'is_featured' => false,
            'is_active' => true,
            'published_at' => now(),
            'seo_title' => null,
            'seo_description' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
            'published_at' => null,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => [
            'stock_quantity' => 0,
            'stock_status' => StockStatus::OutOfStock,
        ]);
    }
}
