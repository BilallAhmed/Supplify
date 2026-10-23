<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_documents', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->string('title');
            $table->string('source_url')->nullable();
            $table->text('searchable_text')->nullable();
            $table->string('content_revision', 64);
            $table->boolean('is_approved')->default(false);
            $table->string('status', 16)->default('active'); // active|removed
            $table->json('meta')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['status', 'is_approved']);
            $table->index('indexed_at');
        });

        Schema::create('assistant_document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_document_id')
                ->constrained('assistant_documents')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('chunk_index');
            $table->string('field_key', 64)->nullable();
            $table->text('content');
            $table->text('normalized_content');
            $table->timestamps();

            $table->unique(['assistant_document_id', 'chunk_index'], 'asst_doc_chunks_doc_idx_unique');
            $table->index('field_key');
        });

        Schema::create('assistant_sync_states', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_document_chunks');
        Schema::dropIfExists('assistant_documents');
        Schema::dropIfExists('assistant_sync_states');
    }
};
