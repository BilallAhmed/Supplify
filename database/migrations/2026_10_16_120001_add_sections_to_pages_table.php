<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pages', 'sections')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->json('sections')->nullable()->after('body');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'sections')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('sections');
            });
        }
    }
};
