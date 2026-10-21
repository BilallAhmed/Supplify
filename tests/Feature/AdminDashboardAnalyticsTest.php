<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductActivity;
use App\Models\ProductView;
use App\Models\User;
use App\Services\CatalogService;
use App\Services\ProductViewTracker;
use App\Services\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_analytics_shell_without_invented_metrics(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Product analytics overview', false)
            ->assertSee('Product detail views', false)
            ->assertSee('No data', false)
            ->assertSee('Recent activity', false)
            ->assertSee('Quick access', false)
            ->assertSee('No product views have been recorded yet', false);
    }

    public function test_storefront_product_view_is_tracked_for_guests_and_dashboard_reflects_it(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'name' => 'Tracked Magnesium',
            'is_active' => true,
            'published_at' => now()->subDay(),
            'is_featured' => true,
        ]);

        $this->get(route('products.show', $product->slug))->assertOk();

        $this->assertDatabaseCount('product_views', 1);
        $this->assertNotNull(app(ProductViewTracker::class)->trackingStartedAt());

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['range' => '7']))
            ->assertOk()
            ->assertSee('Tracked Magnesium', false)
            ->assertSee('Product views over time', false)
            ->assertSee('Top-performing products', false)
            ->assertDontSee('No product views have been recorded yet', false);
    }

    public function test_admin_preview_and_bots_are_not_tracked(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get(route('products.show', $product->slug))
            ->assertOk();

        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])
            ->get(route('products.show', $product->slug))
            ->assertOk();

        $this->assertDatabaseCount('product_views', 0);
    }

    public function test_repeat_view_within_dedupe_window_is_not_double_counted(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->withSession([
            'analytics.visitor_key' => str_repeat('ab', 32),
        ]);

        $this->get(route('products.show', $product->slug))->assertOk();
        $this->get(route('products.show', $product->slug))->assertOk();

        $this->assertDatabaseCount('product_views', 1);
    }

    public function test_product_activity_is_recorded_for_create_update_and_featured_toggle(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $product = app(CatalogService::class)->createProduct([
            'name' => 'Activity Product',
            'sku' => 'ACT-1',
            'price' => '19.99',
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
            'is_active' => true,
            'weight_unit' => 'g',
            'dimension_unit' => 'cm',
            'catalog_visibility' => 'visible',
            'product_type' => 'simple',
            'manage_stock' => true,
            'requires_shipping' => true,
        ]);

        app(CatalogService::class)->updateProduct($product, [
            'name' => 'Activity Product Updated',
            'sku' => 'ACT-1',
            'price' => '21.00',
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
            'is_active' => true,
            'weight_unit' => 'g',
            'dimension_unit' => 'cm',
            'catalog_visibility' => 'visible',
            'product_type' => 'simple',
            'manage_stock' => true,
            'requires_shipping' => true,
        ]);

        $this->post(route('admin.products.featured', $product))->assertRedirect();

        $this->assertTrue(
            ProductActivity::query()->where('product_id', $product->id)->where('action', ProductActivity::ACTION_CREATED)->exists()
        );
        $this->assertTrue(
            ProductActivity::query()->where('product_id', $product->id)->where('action', ProductActivity::ACTION_UPDATED)->exists()
        );
        $this->assertTrue(
            ProductActivity::query()->where('product_id', $product->id)->where('action', ProductActivity::ACTION_FEATURED)->exists()
        );

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Product created', false)
            ->assertSee('Product updated', false)
            ->assertSee('Marked as featured', false);
    }

    public function test_dashboard_date_range_filters_views(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'name' => 'Range Product',
            'is_active' => true,
            'published_at' => now()->subMonth(),
        ]);

        app(StoreSettings::class)->putMany([
            'analytics.product_views_started_at' => now()->subDays(40)->toIso8601String(),
        ]);

        ProductView::query()->create([
            'product_id' => $product->id,
            'visitor_key' => hash('sha256', 'visitor-a'),
            'viewed_at' => Carbon::now()->subDays(20),
        ]);
        ProductView::query()->create([
            'product_id' => $product->id,
            'visitor_key' => hash('sha256', 'visitor-b'),
            'viewed_at' => Carbon::now()->subDays(2),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard', ['range' => '7']))
            ->assertOk();

        $response->assertSee('Range Product', false);

        $sevenDayViews = ProductView::query()
            ->where('viewed_at', '>=', now()->subDays(6)->startOfDay())
            ->count();

        $this->assertSame(1, $sevenDayViews);
        $this->assertSame(1, ProductView::query()->whereBetween('viewed_at', [
            now()->subDays(6)->startOfDay(),
            now()->endOfDay(),
        ])->count());
    }

    public function test_customer_cannot_open_admin_dashboard(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
