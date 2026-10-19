<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'product_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('product_type', 32)->default('simple')->after('sku');
                $table->string('catalog_visibility', 32)->default('visible')->after('is_active');
                $table->boolean('manage_stock')->default(true)->after('stock_quantity');
                $table->boolean('allow_backorder')->default(false)->after('manage_stock');
                $table->boolean('requires_shipping')->default(true)->after('allow_backorder');
                $table->string('shipping_class', 64)->nullable()->after('requires_shipping');
                $table->unsignedInteger('weight_grams')->nullable()->after('shipping_class');
                $table->string('weight_unit', 8)->default('g')->after('weight_grams');
                $table->unsignedInteger('length_mm')->nullable()->after('weight_unit');
                $table->unsignedInteger('width_mm')->nullable()->after('length_mm');
                $table->unsignedInteger('height_mm')->nullable()->after('width_mm');
                $table->string('dimension_unit', 8)->default('cm')->after('height_mm');
                $table->timestamp('sale_starts_at')->nullable()->after('sale_price_cents');
                $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');
                $table->index('product_type');
            });
        }

        if (! Schema::hasTable('product_attributes')) {
            Schema::create('product_attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('used_for_variations')->default(true);
                $table->timestamps();

                $table->unique(['product_id', 'slug']);
            });
        }

        if (! Schema::hasTable('product_attribute_values')) {
            Schema::create('product_attribute_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
                $table->string('value');
                $table->string('slug');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['product_attribute_id', 'slug'], 'attr_value_slug_unique');
            });
        }

        if (! Schema::hasTable('product_variations')) {
            Schema::create('product_variations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('sku')->nullable()->unique();
                $table->boolean('is_enabled')->default(true);
                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('price_cents')->default(0);
                $table->unsignedInteger('sale_price_cents')->nullable();
                $table->timestamp('sale_starts_at')->nullable();
                $table->timestamp('sale_ends_at')->nullable();
                $table->boolean('manage_stock')->default(true);
                $table->unsignedInteger('stock_quantity')->default(0);
                $table->string('stock_status', 32)->default('out_of_stock');
                $table->unsignedInteger('low_stock_threshold')->default(5);
                $table->unsignedInteger('weight_grams')->nullable();
                $table->unsignedInteger('length_mm')->nullable();
                $table->unsignedInteger('width_mm')->nullable();
                $table->unsignedInteger('height_mm')->nullable();
                $table->string('image_path')->nullable();
                $table->string('option_signature', 191);
                $table->json('option_labels')->nullable();
                $table->timestamps();

                $table->unique(['product_id', 'option_signature']);
                $table->index(['product_id', 'is_enabled']);
            });
        }

        if (! Schema::hasTable('product_variation_options')) {
            Schema::create('product_variation_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_variation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_attribute_value_id')->constrained('product_attribute_values')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['product_variation_id', 'product_attribute_id'], 'variation_attr_unique');
            });
        }

        if (! Schema::hasColumn('cart_items', 'line_key')) {
            // MySQL: the composite unique index backs the cart_id FK — add a plain index first.
            Schema::table('cart_items', function (Blueprint $table) {
                $table->index('cart_id', 'cart_items_cart_id_index');
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique(['cart_id', 'product_id']);
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('product_variation_id')->nullable()->after('product_id')
                    ->constrained('product_variations')->nullOnDelete();
                $table->string('line_key', 64)->nullable()->after('product_variation_id');
                $table->json('meta')->nullable()->after('quantity');
            });

            DB::table('cart_items')
                ->whereNull('line_key')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('cart_items')
                            ->where('id', $row->id)
                            ->update(['line_key' => $row->product_id.':0']);
                    }
                });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'line_key'], 'cart_items_line_unique');
            });
        }

        if (! Schema::hasColumn('order_items', 'product_variation_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('product_variation_id')->nullable()->after('product_id')
                    ->constrained('product_variations')->nullOnDelete();
                $table->json('meta')->nullable()->after('line_total_cents');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'product_variation_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_variation_id');
                $table->dropColumn('meta');
            });
        }

        if (Schema::hasColumn('cart_items', 'line_key')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique('cart_items_line_unique');
                $table->dropConstrainedForeignId('product_variation_id');
                $table->dropColumn(['meta', 'line_key']);
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_id']);
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropIndex('cart_items_cart_id_index');
            });
        }

        Schema::dropIfExists('product_variation_options');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');

        if (Schema::hasColumn('products', 'product_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['product_type']);
                $table->dropColumn([
                    'product_type',
                    'catalog_visibility',
                    'manage_stock',
                    'allow_backorder',
                    'requires_shipping',
                    'shipping_class',
                    'weight_grams',
                    'weight_unit',
                    'length_mm',
                    'width_mm',
                    'height_mm',
                    'dimension_unit',
                    'sale_starts_at',
                    'sale_ends_at',
                ]);
            });
        }
    }
};
