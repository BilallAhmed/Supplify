<?php

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_percent_and_fixed_coupons(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'welcome10',
                'type' => CouponType::Percent->value,
                'value' => '15',
                'min_subtotal' => '25.00',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 15,
            'min_subtotal_cents' => 2500,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'SAVE5',
                'type' => CouponType::Fixed->value,
                'value' => '5.00',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'code' => 'SAVE5',
            'type' => 'fixed',
            'value' => 500,
        ]);
    }

    public function test_guest_can_apply_percent_coupon_and_see_discounted_total(): void
    {
        $product = Product::factory()->create([
            'name' => 'Focus Complex',
            'price_cents' => 4000,
            'stock_quantity' => 10,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        Coupon::factory()->percent(10)->create([
            'code' => 'TENOFF',
            'min_subtotal_cents' => null,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->post(route('cart.coupon.apply'), [
            'code' => 'tenoff',
        ])->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('TENOFF', false)
            ->assertSee(Money::format(400), false)
            ->assertSee(Money::format(3600), false);
    }

    public function test_fixed_coupon_cannot_exceed_subtotal(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 300,
            'stock_quantity' => 5,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        Coupon::factory()->fixed(1000)->create(['code' => 'BIG10']);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->post(route('cart.coupon.apply'), ['code' => 'BIG10'])
            ->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee(Money::format(300), false)
            ->assertSee(Money::format(0), false);
    }

    public function test_invalid_and_min_subtotal_coupons_are_rejected(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 2000,
            'stock_quantity' => 5,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        Coupon::factory()->percent(20)->create([
            'code' => 'MIN50',
            'min_subtotal_cents' => 5000,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->from(route('cart.index'))
            ->post(route('cart.coupon.apply'), ['code' => 'NOPE'])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('code');

        $this->from(route('cart.index'))
            ->post(route('cart.coupon.apply'), ['code' => 'MIN50'])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('code');
    }

    public function test_inactive_expired_and_exhausted_coupons_are_rejected(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 5000,
            'stock_quantity' => 5,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        Coupon::factory()->inactive()->create(['code' => 'DEAD']);
        Coupon::factory()->create([
            'code' => 'OLD',
            'ends_at' => now()->subDay(),
        ]);
        Coupon::factory()->create([
            'code' => 'MAXED',
            'max_uses' => 1,
            'used_count' => 1,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        foreach (['DEAD', 'OLD', 'MAXED'] as $code) {
            $this->from(route('cart.index'))
                ->post(route('cart.coupon.apply'), ['code' => $code])
                ->assertRedirect(route('cart.index'))
                ->assertSessionHasErrors('code');
        }
    }

    public function test_guest_can_remove_applied_coupon(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 5000,
            'stock_quantity' => 5,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        Coupon::factory()->percent(10)->create(['code' => 'OFF10']);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->post(route('cart.coupon.apply'), ['code' => 'OFF10'])
            ->assertRedirect(route('cart.index'));

        $this->delete(route('cart.coupon.destroy'))
            ->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertDontSee('OFF10', false)
            ->assertSee(Money::format(5000), false);
    }
}
