<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Services\CheckoutService;
use App\Support\Measurements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCommerceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_shipping_measurements(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Weighted Formula',
            'sku' => 'WGT-001',
            'product_type' => 'simple',
            'price' => '10.00',
            'stock_quantity' => 5,
            'low_stock_threshold' => 2,
            'is_active' => true,
            'requires_shipping' => true,
            'weight' => '0.5',
            'weight_unit' => 'kg',
            'length' => '10',
            'width' => '5',
            'height' => '2',
            'dimension_unit' => 'cm',
            'catalog_visibility' => 'visible',
        ])->assertRedirect();

        $product = Product::query()->where('sku', 'WGT-001')->firstOrFail();
        $this->assertSame(500, $product->weight_grams);
        $this->assertSame(100, $product->length_mm);
        $this->assertSame(50, $product->width_mm);
        $this->assertSame(20, $product->height_mm);
        $this->assertNull(Measurements::weightToGrams(null, 'g'));
        $this->assertSame(0, Measurements::weightToGrams(0, 'g'));
    }

    public function test_featured_and_gallery_images_persist_in_order(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Gallery Product',
            'sku' => 'GAL-001',
            'product_type' => 'simple',
            'price' => '12.00',
            'stock_quantity' => 3,
            'low_stock_threshold' => 1,
            'is_active' => true,
            'catalog_visibility' => 'visible',
            'featured_image' => UploadedFile::fake()->image('main.png', 200, 200),
            'gallery' => [
                UploadedFile::fake()->image('g1.jpg', 180, 180),
                UploadedFile::fake()->image('g2.jpg', 180, 180),
            ],
        ])->assertRedirect();

        $product = Product::query()->where('sku', 'GAL-001')->firstOrFail()->load('images');
        $this->assertTrue($product->primaryImage()?->is_primary);
        $this->assertCount(2, $product->galleryImages());

        $order = $product->galleryImages()->pluck('id')->all();
        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => 'Gallery Product',
            'sku' => 'GAL-001',
            'product_type' => 'simple',
            'price' => '12.00',
            'stock_quantity' => 3,
            'low_stock_threshold' => 1,
            'is_active' => true,
            'catalog_visibility' => 'visible',
            'gallery_order' => array_reverse($order),
            'image_alts' => [
                $order[0] => 'First alt',
            ],
        ])->assertRedirect();

        $product->refresh()->load('images');
        $this->assertSame(array_reverse($order), $product->galleryImages()->pluck('id')->all());
        $this->assertSame('First alt', $product->images->firstWhere('id', $order[0])?->alt);
    }

    public function test_variable_product_variations_and_storefront_cart(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Magnesium Packs',
            'sku' => 'MAG-VAR',
            'product_type' => 'variable',
            'price' => '0',
            'low_stock_threshold' => 2,
            'is_active' => true,
            'catalog_visibility' => 'visible',
            'requires_shipping' => true,
            'weight' => '100',
            'weight_unit' => 'g',
            'dimension_unit' => 'cm',
            'attributes' => [
                [
                    'name' => 'Size',
                    'values' => '30ct, 60ct',
                    'used_for_variations' => '1',
                ],
            ],
            'variations' => [
                [
                    'sku' => 'MAG-VAR-30',
                    'price' => '20.00',
                    'stock_quantity' => 4,
                    'manage_stock' => '1',
                    'is_enabled' => '1',
                    'is_default' => '1',
                    'options' => ['Size' => '30ct'],
                    'weight' => '120',
                ],
                [
                    'sku' => 'MAG-VAR-60',
                    'price' => '34.00',
                    'stock_quantity' => 2,
                    'manage_stock' => '1',
                    'is_enabled' => '1',
                    'is_default' => '0',
                    'options' => ['Size' => '60ct'],
                ],
            ],
        ])->assertRedirect();

        $product = Product::query()->where('sku', 'MAG-VAR')->firstOrFail();
        $this->assertSame(ProductType::Variable, $product->product_type);
        $this->assertCount(2, $product->variations);
        $prices = $product->enabledVariationPrices();
        sort($prices);
        $this->assertSame([2000, 3400], $prices);

        $v30 = ProductVariation::query()->where('sku', 'MAG-VAR-30')->firstOrFail();
        $this->assertSame(120, $v30->weight_grams);
        $this->assertSame(120, $v30->resolvedWeightGrams($product));

        $v60 = ProductVariation::query()->where('sku', 'MAG-VAR-60')->firstOrFail();
        $this->assertNull($v60->weight_grams);
        $this->assertSame(100, $v60->resolvedWeightGrams($product));

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Select options', false)
            ->assertSee('30ct', false);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('variation_id');

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'variation_id' => $v30->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'variation_id' => $v60->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertSame(2, CartItem::query()->count());
        $this->assertNotSame(
            CartItem::query()->where('product_variation_id', $v30->id)->value('line_key'),
            CartItem::query()->where('product_variation_id', $v60->id)->value('line_key')
        );

        $this->post(route('checkout.store'), [
            'email' => 'buyer@example.com',
            'payment_method' => 'cod',
            'billing_same_as_shipping' => true,
            'shipping_name' => 'Buyer',
            'shipping_line1' => '1 Main St',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_postal_code' => '78701',
            'shipping_country' => 'US',
        ])->assertRedirect();

        $this->assertDatabaseCount('order_items', 2);
        $orderItem = OrderItem::query()->where('product_variation_id', $v30->id)->first();
        $this->assertNotNull($orderItem);
        $this->assertSame(['Size' => '30ct'], $orderItem->meta['option_labels'] ?? null);
        $this->assertSame(3, $v30->fresh()->stock_quantity);
    }

    public function test_duplicate_variation_combinations_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Dup Vars',
            'sku' => 'DUP-VAR',
            'product_type' => 'variable',
            'price' => '1',
            'low_stock_threshold' => 1,
            'is_active' => false,
            'catalog_visibility' => 'visible',
            'weight_unit' => 'g',
            'dimension_unit' => 'cm',
            'attributes' => [
                ['name' => 'Size', 'values' => 'S', 'used_for_variations' => '1'],
            ],
            'variations' => [
                [
                    'price' => '10',
                    'stock_quantity' => 1,
                    'is_enabled' => '1',
                    'options' => ['Size' => 'S'],
                ],
                [
                    'price' => '12',
                    'stock_quantity' => 1,
                    'is_enabled' => '1',
                    'options' => ['Size' => 'S'],
                ],
            ],
        ])->assertSessionHasErrors('variations');
    }

    public function test_weight_shipping_requires_known_weights(): void
    {
        config([
            'store.shipping.method' => 'weight',
            'store.shipping.free_over_cents' => 999999,
        ]);

        $product = Product::factory()->create([
            'price_cents' => 2000,
            'stock_quantity' => 5,
            'stock_status' => StockStatus::InStock,
            'is_active' => true,
            'published_at' => now()->subHour(),
            'requires_shipping' => true,
            'weight_grams' => null,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $summary = app(\App\Services\CartService::class)->summary();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(CheckoutService::class)->quote([
            'subtotal_cents' => $summary['subtotal_cents'],
            'discount_cents' => 0,
            'lines' => $summary['lines'],
        ]);
    }

    public function test_existing_simple_products_remain_purchasable(): void
    {
        $product = Product::factory()->create([
            'product_type' => ProductType::Simple,
            'price_cents' => 1500,
            'stock_quantity' => 4,
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'line_key' => $product->id.':0',
            'quantity' => 2,
        ]);
    }
}
