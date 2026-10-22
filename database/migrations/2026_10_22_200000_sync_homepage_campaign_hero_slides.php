<?php

use App\Services\ContentService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('homepage_sections')) {
            return;
        }

        $slides = app(ContentService::class)->defaultHeroSlideDefinitions();
        $first = $slides[0] ?? null;

        $hero = DB::table('homepage_sections')->where('key', 'hero')->first();
        if (! $hero) {
            return;
        }

        $config = json_decode($hero->config ?? '[]', true);
        if (! is_array($config)) {
            $config = [];
        }

        $config['slides'] = $slides;

        DB::table('homepage_sections')->where('key', 'hero')->update([
            'title' => trim(($first['title_accent'] ?? '').' '.($first['title'] ?? '')),
            'subtitle' => $first['eyebrow'] ?? $hero->subtitle,
            'body' => $first['body'] ?? $hero->body,
            'cta_label' => $first['cta_label'] ?? $hero->cta_label,
            'cta_url' => $first['cta_url'] ?? $hero->cta_url,
            'image_path' => $first['scene'] ?? $hero->image_path,
            'config' => json_encode($config, JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Campaign slides supersede lifestyle defaults; restore via prior migration if needed.
    }
};
