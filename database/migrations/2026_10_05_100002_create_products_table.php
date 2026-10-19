<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->longText('benefits')->nullable();
            $table->longText('ingredients')->nullable();
            $table->longText('supplement_facts')->nullable();
            $table->longText('directions')->nullable();
            $table->longText('warnings')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('sale_price_cents')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('stock_status', 32)->default('out_of_stock');
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sku');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('stock_status');
            $table->index(['is_active', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
