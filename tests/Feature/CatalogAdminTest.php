<?php

namespace Tests\Feature;

use App\Enums\StockStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category_and_product(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Energy']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Immunity',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Immunity']);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Vitamin D3',
            'sku' => 'VIT-D3',
            'price' => '24.99',
            'sale_price' => '19.99',
            'stock_quantity' => 25,
            'low_stock_threshold' => 5,
            'is_active' => true,
            'is_featured' => true,
            'category_ids' => [$category->id],
            'tags' => 'vitamin, bone',
            'image' => UploadedFile::fake()->image('d3.jpg', 400, 400),
        ]);

        $product = Product::query()->where('sku', 'VIT-D3')->first();
        $this->assertNotNull($product);
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertSame(2499, $product->price_cents);
        $this->assertSame(1999, $product->sale_price_cents);
        $this->assertSame(StockStatus::InStock, $product->stock_status);
        $this->assertTrue($product->categories->contains('id', $category->id));
        $this->assertTrue($product->images()->exists());
    }

    public function test_inventory_update_syncs_stock_status(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
            'stock_status' => StockStatus::InStock,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.inventory.update', $product), [
                'stock_quantity' => 3,
                'low_stock_threshold' => 5,
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertSame(3, $product->stock_quantity);
        $this->assertSame(StockStatus::LowStock, $product->stock_status);
    }

    public function test_admin_can_duplicate_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create([
            'name' => 'Focus Capsules',
            'sku' => 'FOCUS-1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.products.duplicate', $product));

        $copy = Product::query()->where('name', 'Focus Capsules (Copy)')->first();
        $this->assertNotNull($copy);
        $response->assertRedirect(route('admin.products.edit', $copy));
        $this->assertFalse($copy->is_active);
        $this->assertNotSame($product->sku, $copy->sku);
    }

    public function test_customer_cannot_manage_catalog(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }
}
