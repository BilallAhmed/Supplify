<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_dedicated_admin_sign_in(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Admin sign in');
    }

    public function test_customer_cannot_access_admin_shell(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_dashboard_shell(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Store Admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard', false);
        $response->assertSee('Recent activity', false);
        $response->assertSee('Store Admin', false);
        $response->assertSee('Products', false);
        $response->assertSee('Orders', false);
    }

    public function test_admin_can_open_catalog_and_placeholders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Products', false);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Settings', false)
            ->assertSee('Save settings', false);
    }
}
