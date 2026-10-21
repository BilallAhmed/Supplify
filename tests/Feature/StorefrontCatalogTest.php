<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_lists_published_products_only(): void
    {
        $live = Product::factory()->create([
            'name' => 'Omega Complex',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        Product::factory()->draft()->create(['name' => 'Hidden Draft']);

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee('Omega Complex', false);
        $response->assertDontSee('Hidden Draft', false);
        $response->assertSee(route('products.show', $live->slug), false);
    }

    public function test_shop_can_filter_by_category_search_and_sort(): void
    {
        $energy = Category::factory()->create(['name' => 'Energy', 'slug' => 'energy']);
        $sleep = Category::factory()->create(['name' => 'Sleep', 'slug' => 'sleep']);

        $alpha = Product::factory()->create([
            'name' => 'Alpha Focus',
            'price_cents' => 4000,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        $alpha->categories()->attach($energy);

        $beta = Product::factory()->create([
            'name' => 'Beta Calm',
            'price_cents' => 2000,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        $beta->categories()->attach($sleep);

        $this->get(route('shop.category', 'energy'))
            ->assertOk()
            ->assertSee('Alpha Focus', false)
            ->assertDontSee('Beta Calm', false);

        $this->get(route('shop.index', ['q' => 'Calm']))
            ->assertOk()
            ->assertSee('Beta Calm', false)
            ->assertDontSee('Alpha Focus', false);

        $html = $this->get(route('shop.index', ['sort' => 'price_asc']))
            ->assertOk()
            ->getContent();

        $this->assertTrue(
            strpos($html, 'Beta Calm') < strpos($html, 'Alpha Focus'),
            'Lower-priced product should appear first when sorting by price ascending.'
        );
    }

    public function test_product_detail_page_shows_content_and_hides_drafts(): void
    {
        $category = Category::factory()->create(['name' => 'Recovery', 'slug' => 'recovery']);
        $product = Product::factory()->create([
            'name' => 'Magnesium Night',
            'slug' => 'magnesium-night',
            'short_description' => 'Evening recovery support.',
            'seo_title' => 'Magnesium Night Softgels',
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);
        $product->categories()->attach($category);

        $related = Product::factory()->create([
            'name' => 'Sleep Companion',
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);
        $related->categories()->attach($category);
        $product->relatedProducts()->attach($related->id, ['type' => 'related']);

        $this->get(route('products.show', 'magnesium-night'))
            ->assertOk()
            ->assertSee('Magnesium Night', false)
            ->assertSee('Evening recovery support.', false)
            ->assertSee('Sleep Companion', false)
            ->assertSee('"@type": "Product"', false)
            ->assertSee('"@type": "BreadcrumbList"', false);

        $draft = Product::factory()->draft()->create(['slug' => 'secret-draft']);

        $this->get(route('products.show', $draft->slug))->assertNotFound();
    }

    public function test_inactive_category_returns_not_found(): void
    {
        Category::factory()->create([
            'slug' => 'archived',
            'is_active' => false,
        ]);

        $this->get(route('shop.category', 'archived'))->assertNotFound();
    }

    public function test_shop_all_products_count_is_distinct_not_category_sum(): void
    {
        $energy = Category::factory()->create(['name' => 'Energy', 'slug' => 'energy-count']);
        $sleep = Category::factory()->create(['name' => 'Sleep', 'slug' => 'sleep-count']);

        $shared = Product::factory()->create([
            'name' => 'Shared Formula',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        $shared->categories()->attach([$energy->id, $sleep->id]);

        $onlyEnergy = Product::factory()->create([
            'name' => 'Energy Only',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        $onlyEnergy->categories()->attach($energy);

        $html = $this->get(route('shop.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/All products<\/span>\s*<span class="shop-cat__count">2<\/span>/',
            $html,
            'All products count should be distinct published products, not a sum of category memberships.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/All products<\/span>\s*<span class="shop-cat__count">3<\/span>/',
            $html
        );
    }

    public function test_shop_preserves_filters_when_toggling_availability(): void
    {
        Product::factory()->create([
            'name' => 'Calm Magnesium',
            'is_active' => true,
            'published_at' => now()->subDay(),
            'stock_status' => \App\Enums\StockStatus::InStock,
        ]);

        $this->get(route('shop.index', ['q' => 'Calm', 'sort' => 'name', 'in_stock' => '1']))
            ->assertOk()
            ->assertSee('Calm Magnesium', false)
            ->assertSee('name="q"', false)
            ->assertSee('value="Calm"', false)
            ->assertSee('name="sort"', false)
            ->assertSee('value="name"', false);
    }

    public function test_shop_filters_by_effective_price_range_including_sale(): void
    {
        Product::factory()->create([
            'name' => 'Budget Mineral',
            'price_cents' => 50000,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        Product::factory()->create([
            'name' => 'Sale Focus',
            'price_cents' => 120000,
            'sale_price_cents' => 80000,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        Product::factory()->create([
            'name' => 'Premium Stack',
            'price_cents' => 200000,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('shop.index', ['min_price' => '600', 'max_price' => '900']))
            ->assertOk()
            ->assertSee('Sale Focus', false)
            ->assertDontSee('Budget Mineral', false)
            ->assertDontSee('Premium Stack', false)
            ->assertSee('PKR 600 – 900', false);
    }

    public function test_shop_price_filter_matches_variable_product_by_variation(): void
    {
        $variable = Product::factory()->create([
            'name' => 'Variable Magnesium',
            'product_type' => 'variable',
            'price_cents' => 999999,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        \App\Models\ProductVariation::query()->create([
            'product_id' => $variable->id,
            'sku' => 'VAR-LOW',
            'is_enabled' => true,
            'is_default' => true,
            'price_cents' => 45000,
            'manage_stock' => false,
            'stock_status' => 'in_stock',
            'option_signature' => 'size:30',
            'option_labels' => ['Size' => '30'],
        ]);
        \App\Models\ProductVariation::query()->create([
            'product_id' => $variable->id,
            'sku' => 'VAR-HIGH',
            'is_enabled' => true,
            'is_default' => false,
            'price_cents' => 250000,
            'manage_stock' => false,
            'stock_status' => 'in_stock',
            'option_signature' => 'size:90',
            'option_labels' => ['Size' => '90'],
        ]);

        Product::factory()->create([
            'name' => 'Simple Expensive',
            'price_cents' => 999999,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('shop.index', ['max_price' => '500']))
            ->assertOk()
            ->assertSee('Variable Magnesium', false)
            ->assertDontSee('Simple Expensive', false);
    }

    public function test_shop_invalid_price_range_shows_error_and_skips_filter(): void
    {
        Product::factory()->create([
            'name' => 'Any Product',
            'price_cents' => 100000,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('shop.index', ['min_price' => '900', 'max_price' => '100']))
            ->assertOk()
            ->assertSee('Minimum price cannot be greater than maximum price.', false)
            ->assertSee('Any Product', false)
            ->assertSee('name="min_price"', false)
            ->assertSee('value="900"', false);
    }

    public function test_shop_pagination_uses_branded_pager_and_keeps_filters(): void
    {
        Product::factory()->count(13)->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
            'price_cents' => 100000,
        ]);

        $html = $this->get(route('shop.index', ['min_price' => '10', 'sort' => 'name', 'page' => 1]))
            ->assertOk()
            ->assertSee('shop-pager', false)
            ->assertDontSee('dark:bg-gray-800', false)
            ->getContent();

        $this->assertStringContainsString('min_price=10', $html);
        $this->assertStringContainsString('sort=name', $html);
    }
}
