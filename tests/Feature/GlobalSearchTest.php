<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_matching_products_and_pages(): void
    {
        $product = Product::factory()->create([
            'name' => 'Magnesium Night',
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        Product::factory()->create([
            'name' => 'Omega Complex',
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $page = Page::factory()->create([
            'title' => 'About Magnesium',
            'slug' => 'about-magnesium',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        Page::factory()->create([
            'title' => 'Shipping',
            'slug' => 'shipping-test',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->getJson(route('search', ['q' => 'Magnesium']));

        $response->assertOk()
            ->assertJsonPath('q', 'Magnesium')
            ->assertJsonPath('total', 2);

        $titles = collect($response->json('results'))->pluck('title');

        $this->assertTrue($titles->contains('Magnesium Night'));
        $this->assertTrue($titles->contains('About Magnesium'));
        $this->assertFalse($titles->contains('Omega Complex'));
        $this->assertFalse($titles->contains('Shipping'));

        $productResult = collect($response->json('results'))->firstWhere('type', 'product');
        $this->assertSame(route('products.show', $product->slug), $productResult['url']);
        $this->assertNotEmpty($productResult['image']);

        $pageResult = collect($response->json('results'))->firstWhere('type', 'page');
        $this->assertSame(route('pages.show', $page->slug), $pageResult['url']);
    }

    public function test_search_requires_two_characters_and_hides_drafts(): void
    {
        Product::factory()->create([
            'name' => 'Daily Greens',
            'is_active' => false,
            'published_at' => null,
        ]);

        Page::factory()->draft()->create([
            'title' => 'Daily Routine Draft',
        ]);

        $this->getJson(route('search', ['q' => 'D']))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('results', []);

        $this->getJson(route('search', ['q' => 'Daily']))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_header_includes_search_module(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('site-header__search', false)
            ->assertSee('header-search-input', false);
    }
}
