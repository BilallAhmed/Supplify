<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('intent', 64)->nullable();
            $table->json('aliases')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index('intent');
        });

        Schema::create('assistant_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('phrase');
            $table->string('normalized_phrase')->index();
            $table->string('locale', 16)->default('en');
            $table->string('maps_to_intent', 64)->nullable();
            $table->string('target_type', 32)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'normalized_phrase']);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('assistant_source_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->timestamp('approved_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index('approved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_source_approvals');
        Schema::dropIfExists('assistant_aliases');
        Schema::dropIfExists('assistant_faqs');
    }
};
