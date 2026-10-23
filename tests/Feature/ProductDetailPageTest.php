<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_product_page_renders_gallery_and_organized_sections(): void
    {
        $this->seed(CmsSeeder::class);

        $product = Product::query()->where('slug', 'magnesium-250mg-90-tablets')->first()
            ?? Product::factory()->create([
                'name' => 'Magnesium 250mg',
                'slug' => 'magnesium-250mg-90-tablets',
                'short_description' => 'Supplify Magnesium 250mg dietary supplement, 90 tablets.',
                'description' => 'Expanded description for preview.',
                'benefits' => "Mineral support for everyday routines*\nConvenient tablet format",
                'ingredients' => 'Vegetable cellulose.',
                'supplement_facts' => "Serving Size: 1 tablet\nMagnesium: 250 mg",
                'directions' => 'Take 1 tablet daily.',
                'warnings' => 'Keep out of reach of children.',
                'is_active' => true,
                'published_at' => now()->subHour(),
            ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('productPdp', false);
        $response->assertSee('pdp__stage', false);
        $response->assertSee('Add to cart', false);
        $response->assertSee('Hover to magnify', false);
        $response->assertSee('About this formula', false);
        $response->assertSee('Key benefits', false);
        $response->assertSee('Formula details', false);
        $response->assertDontSee('Continue shopping', false);
    }

    public function test_temporary_pricing_shows_awaiting_state_not_out_of_stock_only(): void
    {
        $product = Product::factory()->create([
            'name' => 'Preview Formula',
            'slug' => 'preview-formula',
            'is_active' => true,
            'published_at' => now()->subHour(),
            'stock_status' => 'in_stock',
        ]);
        $product->tags()->attach(
            \App\Models\Tag::query()->firstOrCreate(
                ['slug' => 'temporary-pricing'],
                ['name' => 'Temporary pricing']
            )
        );

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('Awaiting confirmed pricing', false);
    }
}
