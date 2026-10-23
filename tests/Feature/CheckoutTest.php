<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderPlacedNotification;
use App\Services\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_cart_cannot_open_checkout(): void
    {
        $this->get(route('checkout.show'))
            ->assertRedirect(route('cart.index'));
    }

    public function test_guest_can_place_cod_order_with_stock_decrement_and_email(): void
    {
        app(StoreSettings::class)->putMany(['currency' => 'PKR']);
        Notification::fake();

        config([
            'store.shipping.flat_rate_cents' => 599,
            'store.shipping.free_over_cents' => 7500,
            'store.tax.rate_percent' => 0,
        ]);

        $product = Product::factory()->create([
            'name' => 'Omega Complex',
            'price_cents' => 4000,
            'stock_quantity' => 10,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Cash on delivery', false)
            ->assertSee('Omega Complex', false);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload([
            'email' => 'guest@example.com',
        ]));

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('checkout.confirmation', $order));

        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame(PaymentMethod::Cod, $order->payment_method);
        $this->assertSame(4000, $order->subtotal_cents);
        $this->assertSame(599, $order->shipping_cents);
        $this->assertSame(4599, $order->total_cents);
        $this->assertSame('PKR', $order->currency);
        $this->assertSame('PKR 45.99', $order->totalFormatted());
        $mail = (new OrderPlacedNotification($order))->toMail(new \stdClass);
        $this->assertStringContainsString('PKR 40.00', implode(' ', $mail->introLines));
        $this->assertSame(9, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('cart_items', 0);

        Notification::assertSentOnDemand(OrderPlacedNotification::class);

        $this->get(route('checkout.confirmation', $order))
            ->assertOk()
            ->assertSee($order->order_number, false)
            ->assertSee('cash on delivery', false);
    }

    public function test_free_shipping_over_threshold_and_coupon_discount(): void
    {
        Notification::fake();

        config([
            'store.shipping.flat_rate_cents' => 599,
            'store.shipping.free_over_cents' => 7500,
            'store.tax.rate_percent' => 0,
        ]);

        $product = Product::factory()->create([
            'price_cents' => 9000,
            'stock_quantity' => 5,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        Coupon::factory()->percent(10)->create(['code' => 'TENOFF']);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->post(route('cart.coupon.apply'), ['code' => 'TENOFF'])
            ->assertRedirect(route('cart.index'));

        $this->post(route('checkout.store'), $this->validCheckoutPayload())
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(9000, $order->subtotal_cents);
        $this->assertSame(900, $order->discount_cents);
        $this->assertSame(0, $order->shipping_cents);
        $this->assertSame(8100, $order->total_cents);
        $this->assertSame('TENOFF', $order->coupon_code);
        $this->assertSame(1, Coupon::query()->where('code', 'TENOFF')->value('used_count'));
    }

    public function test_tax_is_applied_from_store_config(): void
    {
        Notification::fake();

        config([
            'store.shipping.flat_rate_cents' => 500,
            'store.shipping.free_over_cents' => 999999,
            'store.tax.rate_percent' => 10,
        ]);

        $product = Product::factory()->create([
            'price_cents' => 1000,
            'stock_quantity' => 5,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->post(route('checkout.store'), $this->validCheckoutPayload())
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertSame(150, $order->tax_cents);
        $this->assertSame(1650, $order->total_cents);
    }

    public function test_checkout_validation_requires_shipping_fields(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 2000,
            'stock_quantity' => 5,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->from(route('checkout.show'))
            ->post(route('checkout.store'), [
                'email' => 'not-an-email',
                'billing_same_as_shipping' => '1',
            ])
            ->assertRedirect(route('checkout.show'))
            ->assertSessionHasErrors(['email', 'shipping_name', 'shipping_line1', 'shipping_city', 'shipping_state', 'shipping_postal_code']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validCheckoutPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'buyer@example.com',
            'phone' => '555-0100',
            'payment_method' => PaymentMethod::Cod->value,
            'billing_same_as_shipping' => '1',
            'shipping_name' => 'Alex Buyer',
            'shipping_line1' => '123 Wellness Ave',
            'shipping_line2' => '',
            'shipping_city' => 'Austin',
            'shipping_state' => 'Sindh',
            'shipping_postal_code' => '78701',
            'shipping_country' => 'PK',
            'shipping_phone' => '555-0100',
            'notes' => 'Leave at door',
        ], $overrides);
    }

    public function test_checkout_restricts_delivery_to_pakistan(): void
    {
        $this->post(route('checkout.store'), $this->validCheckoutPayload(['shipping_country' => 'US']))
            ->assertSessionHasErrors('shipping_country');
    }

    public function test_checkout_rejects_a_province_outside_the_selected_country(): void
    {
        $this->post(route('checkout.store'), $this->validCheckoutPayload(['shipping_country' => 'PK', 'shipping_state' => 'Texas']))
            ->assertSessionHasErrors('shipping_state');
    }
}
