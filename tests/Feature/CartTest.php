<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_update_and_remove_cart_items(): void
    {
        $product = Product::factory()->create([
            'name' => 'Magnesium Night',
            'price_cents' => 3200,
            'sale_price_cents' => 2900,
            'stock_quantity' => 10,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Magnesium Night', false)
            ->assertSee(Money::format(5800), false);

        $item = CartItem::query()->first();
        $this->assertNotNull($item);
        $this->assertSame(2, $item->quantity);

        $this->patch(route('cart.items.update', $item), [
            'quantity' => 3,
        ])->assertRedirect(route('cart.index'));

        $this->assertSame(3, $item->fresh()->quantity);

        $this->delete(route('cart.items.destroy', $item))
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 2,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->from(route('products.show', $product->slug))
            ->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('quantity');
    }

    public function test_guest_cart_merges_into_user_cart_on_login(): void
    {
        $productA = Product::factory()->create([
            'name' => 'Omega Complex',
            'price_cents' => 4000,
            'stock_quantity' => 8,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);
        $productB = Product::factory()->create([
            'name' => 'Daily Greens',
            'price_cents' => 3500,
            'stock_quantity' => 8,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $customer = User::factory()->customer()->create([
            'email' => 'buyer@example.com',
            'password' => 'password',
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $productA->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->post(route('login'), [
            'email' => 'buyer@example.com',
            'password' => 'password',
        ])->assertRedirect();

        $this->post(route('cart.items.store'), [
            'product_id' => $productB->id,
            'quantity' => 2,
        ])->assertRedirect();

        $userCart = Cart::query()->where('user_id', $customer->id)->first();
        $this->assertNotNull($userCart);
        $this->assertSame(2, $userCart->items()->count());
        $this->assertSame(1, $userCart->items()->where('product_id', $productA->id)->value('quantity'));
        $this->assertSame(2, $userCart->items()->where('product_id', $productB->id)->value('quantity'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Omega Complex', false)
            ->assertSee('Daily Greens', false)
            ->assertSee(Money::format(4000 + 7000), false);
    }

    public function test_header_shows_cart_count_after_add(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('site-header__badge', false)
            ->assertSee('>2<', false);
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $product = Product::factory()->outOfStock()->create([
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->from(route('products.show', $product->slug))
            ->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('product');
    }
}
