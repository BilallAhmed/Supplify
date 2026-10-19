<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCommerceActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_view_returns_published_product_payload(): void
    {
        $product = Product::factory()->create([
            'name' => 'Quick View Magnesium',
            'slug' => 'quick-view-magnesium',
            'is_active' => true,
            'published_at' => now()->subHour(),
            'short_description' => 'A calm nightly mineral.',
        ]);

        $this->getJson(route('products.quick-view', $product->slug))
            ->assertOk()
            ->assertJsonPath('name', 'Quick View Magnesium')
            ->assertJsonPath('slug', 'quick-view-magnesium')
            ->assertJsonStructure(['gallery', 'pdp', 'url', 'cart_url']);
    }

    public function test_guest_can_toggle_wishlist_in_session(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->postJson(route('wishlist.toggle', $product))
            ->assertOk()
            ->assertJsonPath('saved', true);

        $this->getJson(route('wishlist.index'))
            ->assertOk()
            ->assertJsonPath('ids.0', $product->id);

        $this->postJson(route('wishlist.toggle', $product))
            ->assertOk()
            ->assertJsonPath('saved', false);
    }

    public function test_wishlist_merges_into_account_on_login(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);
        $user = User::factory()->create([
            'is_active' => true,
            'password' => bcrypt('Password1!'),
        ]);

        $this->postJson(route('wishlist.toggle', $product))->assertOk();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password1!',
        ])->assertRedirect();

        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->assertSame(1, WishlistItem::query()->where('user_id', $user->id)->count());
    }

    public function test_homepage_shows_explore_more_and_view_all_products(): void
    {
        $this->seed(CmsSeeder::class);

        $featured = Product::factory()->create([
            'name' => 'Featured Rail Item',
            'is_featured' => true,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        Product::factory()->create([
            'name' => 'Explore Catalog Item',
            'is_featured' => false,
            'is_active' => true,
            'published_at' => now()->subMinutes(30),
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('View All Products', false);
        $response->assertSee('Explore More Formulas', false);
        $response->assertSee('Explore Catalog Item', false);
        $response->assertSee('Featured Rail Item', false);
        $response->assertSee('Quick View', false);
        $response->assertSee($featured->name, false);
    }

    public function test_temporary_pricing_product_cannot_be_added_via_json_cart(): void
    {
        $product = Product::factory()->create([
            'is_active' => true,
            'published_at' => now()->subHour(),
            'stock_quantity' => 10,
        ]);

        $tag = \App\Models\Tag::query()->firstOrCreate(
            ['slug' => 'temporary-pricing'],
            ['name' => 'Temporary pricing']
        );
        $product->tags()->syncWithoutDetaching([$tag->id]);

        $this->postJson(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertStatus(422);
    }
}
