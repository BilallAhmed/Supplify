<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_view_customers(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create([
            'name' => 'Alex Buyer',
            'email' => 'alex@example.com',
            'phone' => '555-0100',
        ]);
        User::factory()->admin()->create(['email' => 'other-admin@example.com']);

        Address::query()->create([
            'user_id' => $customer->id,
            'type' => 'shipping',
            'name' => 'Alex Buyer',
            'line1' => '123 Main St',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'country' => 'US',
            'is_default' => true,
        ]);

        Order::factory()->create([
            'user_id' => $customer->id,
            'order_number' => 'SUP-CUST-0001',
            'email' => $customer->email,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Alex Buyer', false)
            ->assertSee('alex@example.com', false)
            ->assertDontSee('Coming next', false);

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('123 Main St', false)
            ->assertSee('SUP-CUST-0001', false);
    }

    public function test_admin_can_update_customer_and_cannot_open_admin_as_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create(['is_active' => true]);
        $otherAdmin = User::factory()->admin()->create(['email' => 'peer@example.com']);

        $this->actingAs($admin)
            ->put(route('admin.customers.update', $customer), [
                'name' => 'Updated Name',
                'phone' => '555-9999',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.customers.show', $customer));

        $customer->refresh();
        $this->assertSame('Updated Name', $customer->name);
        $this->assertSame('555-9999', $customer->phone);
        $this->assertFalse($customer->is_active);

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $otherAdmin))
            ->assertNotFound();
    }

    public function test_customer_cannot_access_admin_customers(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }
}
