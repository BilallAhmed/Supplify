<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // public_path = files under public/ (e.g. images/about/…)
            // public = Laravel public disk under storage/app/public
            $table->string('disk', 32);
            $table->string('path', 500);
            $table->string('original_filename', 255);
            $table->string('title', 255)->nullable();
            $table->string('alt', 255)->nullable();
            $table->string('mime_type', 120);
            $table->string('extension', 20);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum', 64)->nullable()->index();
            $table->string('source', 20)->default('uploaded'); // imported|uploaded
            $table->boolean('is_locked')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index(['original_filename']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
