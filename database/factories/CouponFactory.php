<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE-####')),
            'type' => CouponType::Percent,
            'value' => 10,
            'min_subtotal_cents' => null,
            'max_uses' => null,
            'used_count' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    public function fixed(int $cents = 500): static
    {
        return $this->state(fn () => [
            'type' => CouponType::Fixed,
            'value' => $cents,
        ]);
    }

    public function percent(int $percent = 10): static
    {
        return $this->state(fn () => [
            'type' => CouponType::Percent,
            'value' => $percent,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
