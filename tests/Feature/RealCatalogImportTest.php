<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RealCatalogImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_real_products_archives_dummies_and_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        // Recreate legacy dummy SKUs the way the old seeder did (categories-only seeder no longer does).
        foreach ([
            ['sku' => 'OMG-1000', 'name' => 'Omega Complex'],
            ['sku' => 'MAG-NITE', 'name' => 'Magnesium Night'],
        ] as $dummy) {
            Product::factory()->create([
                'sku' => $dummy['sku'],
                'name' => $dummy['name'],
                'is_active' => true,
                'is_featured' => true,
                'published_at' => now()->subHour(),
            ]);
        }

        $this->assertTrue(Category::query()->where('slug', 'energy-focus')->exists());

        Artisan::call('catalog:import-real-products', ['--skip-snapshot' => true]);

        $this->assertSame(15, Product::query()->count());
        $this->assertSame(0, Product::query()->whereIn('sku', ['OMG-1000', 'MAG-NITE'])->count());
        $this->assertSame(2, Product::onlyTrashed()->whereIn('sku', ['OMG-1000', 'MAG-NITE'])->count());

        $magnesium = Product::query()->where('sku', 'SUP-MAGNESIUM-250MG-90')->with(['images', 'tags'])->first();
        $this->assertNotNull($magnesium);
        $this->assertFalse($magnesium->isPurchasable());
        $this->assertTrue($magnesium->hasTemporaryPricing());
        $this->assertTrue($magnesium->hasEditorialDraft());
        $this->assertSame('images/products/1S5A3686.png', $magnesium->primaryImage()?->path);
        $this->assertTrue($magnesium->images->contains('path', 'images/products/1S5A3687.png'));
        $this->assertSame(100000, $magnesium->price_cents);

        Artisan::call('catalog:import-real-products', ['--skip-snapshot' => true]);
        $this->assertSame(15, Product::query()->count());
        $this->assertSame(1, Product::query()->where('sku', 'SUP-MAGNESIUM-250MG-90')->count());
        $this->assertSame(2, Product::query()->where('sku', 'SUP-MAGNESIUM-250MG-90')->first()->images()->count());
    }
}
