<?php

namespace Database\Factories;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2000, 12000);
        $shipping = 599;
        $total = $subtotal + $shipping;

        return [
            'order_number' => 'SUP-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'email' => fake()->safeEmail(),
            'phone' => '555-0100',
            'shipping_name' => fake()->name(),
            'shipping_line1' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_state' => 'CA',
            'shipping_postal_code' => fake()->postcode(),
            'shipping_country' => 'US',
            'billing_name' => fake()->name(),
            'billing_line1' => fake()->streetAddress(),
            'billing_city' => fake()->city(),
            'billing_state' => 'CA',
            'billing_postal_code' => fake()->postcode(),
            'billing_country' => 'US',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => PaymentMethod::Cod,
            'subtotal_cents' => $subtotal,
            'discount_cents' => 0,
            'shipping_cents' => $shipping,
            'tax_cents' => 0,
            'total_cents' => $total,
            'currency' => 'USD',
            'placed_at' => now(),
        ];
    }
}
