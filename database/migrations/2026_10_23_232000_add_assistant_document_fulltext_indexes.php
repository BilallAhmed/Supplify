<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optional MySQL FULLTEXT indexes. SQLite and drivers without FULLTEXT skip safely.
 * Portable token search remains the documented fallback (config assistant.search.driver).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('assistant_documents')) {
            return;
        }

        $existing = collect(DB::select("SHOW INDEX FROM assistant_documents WHERE Key_name = 'asst_docs_ft'"));
        if ($existing->isEmpty()) {
            DB::statement('ALTER TABLE assistant_documents ADD FULLTEXT asst_docs_ft (title, searchable_text)');
        }

        if (Schema::hasTable('assistant_document_chunks')) {
            $chunkFt = collect(DB::select("SHOW INDEX FROM assistant_document_chunks WHERE Key_name = 'asst_chunks_ft'"));
            if ($chunkFt->isEmpty()) {
                DB::statement('ALTER TABLE assistant_document_chunks ADD FULLTEXT asst_chunks_ft (normalized_content)');
            }
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::statement('ALTER TABLE assistant_documents DROP INDEX asst_docs_ft');
        } catch (\Throwable) {
            //
        }

        try {
            DB::statement('ALTER TABLE assistant_document_chunks DROP INDEX asst_chunks_ft');
        } catch (\Throwable) {
            //
        }
    }
};
