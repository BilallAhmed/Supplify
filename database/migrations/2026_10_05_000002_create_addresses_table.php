<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->default('shipping'); // shipping|billing|both
            $table->string('name');
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('state', 64)->nullable();
            $table->string('postal_code', 32);
            $table->string('country', 2)->default('US');
            $table->string('phone', 32)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
