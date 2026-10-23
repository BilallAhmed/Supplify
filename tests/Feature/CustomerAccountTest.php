<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_account_area(): void
    {
        $this->get(route('account.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_can_view_dashboard_and_own_orders(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $product = Product::factory()->create();

        $own = Order::factory()->create([
            'user_id' => $customer->id,
            'order_number' => 'SUP-OWN-0001',
            'status' => OrderStatus::Processing,
            'payment_method' => PaymentMethod::Cod,
        ]);
        OrderItem::query()->create([
            'order_id' => $own->id,
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'unit_price_cents' => 2000,
            'quantity' => 1,
            'line_total_cents' => 2000,
        ]);

        $foreign = Order::factory()->create([
            'user_id' => $other->id,
            'order_number' => 'SUP-OTHER-0001',
        ]);

        $this->actingAs($customer)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('SUP-OWN-0001', false)
            ->assertDontSee('SUP-OTHER-0001', false);

        $this->actingAs($customer)
            ->get(route('account.orders.index'))
            ->assertOk()
            ->assertSee('SUP-OWN-0001', false);

        $this->actingAs($customer)
            ->get(route('account.orders.show', $own))
            ->assertOk()
            ->assertSee($product->name, false);

        $this->actingAs($customer)
            ->get(route('account.orders.show', $foreign))
            ->assertForbidden();
    }

    public function test_customer_can_manage_addresses(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->post(route('account.addresses.store'), [
                'type' => 'shipping',
                'name' => 'Alex Buyer',
                'line1' => '100 Green St',
                'city' => 'Austin',
                'state' => 'Sindh',
                'postal_code' => '78701',
                'country' => 'PK',
                'phone' => '555-0100',
                'is_default' => '1',
            ])
            ->assertRedirect(route('account.addresses.index'));

        $address = Address::query()->first();
        $this->assertNotNull($address);
        $this->assertTrue($address->is_default);
        $this->assertSame($customer->id, $address->user_id);

        $this->actingAs($customer)
            ->put(route('account.addresses.update', $address), [
                'type' => 'both',
                'name' => 'Alex Buyer',
                'line1' => '200 Leaf Ave',
                'city' => 'Austin',
                'state' => 'Sindh',
                'postal_code' => '78702',
                'country' => 'PK',
                'is_default' => '1',
            ])
            ->assertRedirect(route('account.addresses.index'));

        $this->assertSame('200 Leaf Ave', $address->fresh()->line1);

        $this->actingAs($customer)
            ->delete(route('account.addresses.destroy', $address))
            ->assertRedirect(route('account.addresses.index'));

        $this->assertDatabaseCount('addresses', 0);
    }

    public function test_customer_cannot_edit_another_users_address(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $address = Address::query()->create([
            'user_id' => $other->id,
            'type' => 'shipping',
            'name' => 'Other Person',
            'line1' => '1 Other St',
            'city' => 'Dallas',
            'state' => 'Sindh',
            'postal_code' => '75201',
            'country' => 'PK',
            'is_default' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('account.addresses.edit', $address))
            ->assertForbidden();
    }
}
