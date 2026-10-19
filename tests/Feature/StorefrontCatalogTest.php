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
}
