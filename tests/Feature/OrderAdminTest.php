<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_view_order(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create([
            'order_number' => 'SUP-TEST-0001',
            'email' => 'buyer@example.com',
            'payment_method' => PaymentMethod::Cod,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'unit_price_cents' => 2500,
            'quantity' => 2,
            'line_total_cents' => 5000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('SUP-TEST-0001', false)
            ->assertSee('buyer@example.com', false);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($product->name, false)
            ->assertSee('Cash on delivery', false);
    }

    public function test_admin_can_update_order_statuses(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Shipped->value,
                'payment_status' => PaymentStatus::Paid->value,
                'fulfillment_status' => FulfillmentStatus::Fulfilled->value,
                'note' => 'COD collected and shipped.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(FulfillmentStatus::Fulfilled, $order->fulfillment_status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => OrderStatus::Shipped->value,
            'note' => 'COD collected and shipped.',
        ]);
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.orders.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.orders.show', $order))
            ->assertForbidden();
    }
}
