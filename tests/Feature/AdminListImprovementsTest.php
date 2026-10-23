<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListImprovementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_combine_payment_fulfillment_and_inclusive_dates(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $match = Order::factory()->create(['payment_status' => PaymentStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Fulfilled, 'placed_at' => '2026-10-20 23:59:59']);
        Order::factory()->create(['payment_status' => PaymentStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Fulfilled, 'placed_at' => '2026-10-21 00:00:00']);
        $this->get(route('admin.orders.index', ['payment_status' => 'paid', 'fulfillment_status' => 'fulfilled', 'from' => '2026-10-20', 'to' => '2026-10-20']))->assertOk()->assertViewHas('orders', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $match->id);
        $this->get(route('admin.orders.index', ['to' => '2026-10-20']))->assertOk();
        $this->get(route('admin.orders.index', ['from' => '2026-10-21', 'to' => '2026-10-20']))->assertSessionHasErrors('to');
    }

    public function test_coupon_expiry_filter_excludes_open_ended_codes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $expired = Coupon::factory()->create(['ends_at' => now()->subDay()]);
        Coupon::factory()->create();
        Coupon::factory()->create(['ends_at' => now()->addDays(10)]);
        $this->get(route('admin.coupons.index', ['expiry' => 'expired']))->assertOk()->assertViewHas('coupons', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $expired->id);
        $this->get(route('admin.coupons.index', ['expiry' => 'upcoming']))->assertOk()->assertViewHas('coupons', fn ($rows) => $rows->count() === 1);
    }

    public function test_blog_category_and_scheduled_status_combine(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $category = PostCategory::query()->create(['name' => 'Wellness', 'slug' => 'wellness']);
        $scheduled = Post::factory()->create(['post_category_id' => $category->id, 'published_at' => now()->addDay()]);
        Post::factory()->create(['post_category_id' => $category->id]);
        Post::factory()->draft()->create();
        $this->get(route('admin.blog.index', ['status' => 'scheduled', 'category' => $category->id]))->assertOk()->assertViewHas('posts', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $scheduled->id)->assertSee('Scheduled');
    }

    public function test_variation_stock_is_scoped_and_parent_status_updates(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $parent = Product::factory()->create(['product_type' => ProductType::Variable]);
        $other = Product::factory()->create(['product_type' => ProductType::Variable]);
        $variation = $parent->variations()->create(['sku' => 'VAR-STOCK-1', 'is_enabled' => true, 'price_cents' => 1000, 'manage_stock' => true, 'stock_quantity' => 5, 'low_stock_threshold' => 2, 'stock_status' => 'in_stock', 'option_signature' => 'one', 'option_labels' => ['Size' => 'Small']]);
        $data = ['stock_quantity' => 0, 'low_stock_threshold' => 2, 'manage_stock' => 1];
        $this->put(route('admin.inventory.variations.update', [$other, $variation->id]), $data)->assertNotFound();
        $this->assertSame(5, $variation->fresh()->stock_quantity);
        $this->put(route('admin.inventory.variations.update', [$parent, $variation->id]), $data)->assertRedirect();
        $this->assertSame('out_of_stock', $variation->fresh()->stock_status->value);
        $this->assertSame('out_of_stock', $parent->fresh()->stock_status->value);
        $this->put(route('admin.inventory.variations.update', [$parent, $variation->id]), array_replace($data, ['stock_quantity' => -1]))->assertSessionHasErrors('stock_quantity');
        $this->get(route('admin.inventory.index', ['q' => 'VAR-STOCK-1']))->assertOk()->assertSee('VAR-STOCK-1')->assertSee('Track stock');
        $this->actingAs(User::factory()->create())->put(route('admin.inventory.variations.update', [$parent, $variation->id]), $data)->assertForbidden();
    }
}
