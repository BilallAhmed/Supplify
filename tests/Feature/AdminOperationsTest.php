<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContactEnquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\StoreOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_entry_is_separate_from_customer_login(): void
    {
        $this->get('/admin')->assertOk()->assertSee('Admin sign in')->assertSee(route('admin.login.store'), false)->assertDontSee('Create an account');
        $this->get('/login')->assertOk()->assertSee('Access your orders and account details.')->assertSee(route('login.store'), false)->assertDontSee('Store administration');
        $this->get('/admin/products')->assertRedirect('/admin');
        $this->get('/admin/analytics')->assertRedirect('/admin');
    }

    public function test_admin_credentials_are_rejected_by_customer_login(): void
    {
        $user = User::factory()->admin()->create(['password' => 'password']);
        $this->from('/login')->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'role' => 'admin'])->assertSessionHasErrors('email')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_customer_credentials_are_rejected_by_admin_login(): void
    {
        $user = User::factory()->customer()->create(['password' => 'password']);
        $this->from('/admin')->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email')->assertRedirect('/admin');
        $this->assertGuest();
    }

    public function test_admin_login_preserves_an_intended_admin_page_and_logout_returns_to_admin(): void
    {
        $user = User::factory()->admin()->create(['password' => 'password']);
        $this->withSession(['url.intended' => route('admin.products.index')])->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('admin.products.index'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('admin.logout'))->assertRedirect('/admin');
        $this->assertGuest();
    }

    public function test_customer_login_does_not_follow_an_admin_intended_url(): void
    {
        $user = User::factory()->customer()->create(['password' => 'password']);
        $this->withSession(['url.intended' => route('admin.products.index')])->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_admin_cannot_sign_in_or_use_existing_admin_session(): void
    {
        $user = User::factory()->admin()->inactive()->create(['password' => 'password']);
        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($user)->get('/admin')->assertRedirect('/admin');
        $this->assertGuest();
    }

    public function test_admin_sign_in_keeps_the_failed_attempt_limit(): void
    {
        $user = User::factory()->admin()->create(['password' => 'password']);
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_operational_dashboard_has_real_counts_without_analytics_charts(): void
    {
        User::factory()->customer()->count(2)->create();
        $admin = User::factory()->admin()->create();
        Order::factory()->create(['status' => 'pending']);
        Order::factory()->create(['status' => 'completed', 'fulfillment_status' => 'fulfilled']);
        Category::factory()->count(2)->create();
        Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 3, 'product_type' => 'simple', 'stock_status' => 'low_stock']);
        Product::factory()->create(['manage_stock' => false, 'stock_quantity' => 900, 'product_type' => 'simple']);
        $parent = Product::factory()->create(['product_type' => 'variable', 'manage_stock' => true, 'stock_quantity' => 100]);
        $parent->variations()->create(['sku' => 'OP-V1', 'is_enabled' => true, 'manage_stock' => true, 'stock_quantity' => 7, 'stock_status' => 'in_stock', 'option_signature' => 'test-1']);
        $parent->variations()->create(['sku' => 'OP-V2', 'is_enabled' => false, 'manage_stock' => true, 'stock_quantity' => 50, 'stock_status' => 'in_stock', 'option_signature' => 'test-2']);
        ContactEnquiry::create(['name' => 'Customer', 'email' => 'test@example.com', 'enquiry_type' => 'other', 'message' => 'A question', 'is_read' => false]);
        $overview = app(StoreOverviewService::class)->build();
        $stats = collect($overview['stats'])->pluck('value', 'label');
        $this->assertSame(2, $stats['Total orders']);
        $this->assertSame(3, $stats['Products']);
        $this->assertSame(2, $stats['Categories']);
        $this->assertSame(10, $stats['Inventory units']);
        $this->assertSame(2, $stats['Customers']);
        $this->assertSame(1, $stats['Pending orders']);
        $this->assertSame(1, $stats['Unread enquiries']);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Needs attention')->assertSee('Recent orders')->assertSee('Quick links')->assertDontSee('admin-product-views-chart', false)->assertDontSee('Product views over time');
    }

    public function test_tracking_is_inside_settings_and_analytics_is_a_separate_overview_tab(): void
    {
        $admin = User::factory()->admin()->create();
        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk()->assertSee(route('admin.analytics'), false)->assertDontSee('Tracking &amp; Scripts', false);
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee(route('admin.settings.tracking.edit'), false);
        $this->get(route('admin.settings.tracking.edit'))->assertOk()->assertSee('Settings / Tracking &amp; Scripts', false);
        $this->get(route('admin.analytics'))->assertOk()->assertSee('Product views over time');
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.analytics'))->assertForbidden();
    }
}
