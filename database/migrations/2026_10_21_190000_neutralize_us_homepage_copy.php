<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $trust = DB::table('homepage_sections')->where('key', 'trust')->first();
        if ($trust) {
            $config = json_decode($trust->config ?? '[]', true) ?: [];
            $items = $config['items'] ?? [];
            foreach ($items as &$item) {
                if (($item['title'] ?? '') === 'US-focused fulfillment') {
                    $item['title'] = 'Careful fulfillment';
                    $item['body'] = 'Orders prepared for reliable domestic delivery.';
                }
            }
            unset($item);
            $config['items'] = $items;
            DB::table('homepage_sections')->where('id', $trust->id)->update([
                'config' => json_encode($config),
                'updated_at' => now(),
            ]);
        }

        $story = DB::table('homepage_sections')->where('key', 'story')->first();
        if ($story) {
            $config = json_decode($story->config ?? '[]', true) ?: [];
            $beats = $config['beats'] ?? [];
            foreach ($beats as &$beat) {
                $body = (string) ($beat['body'] ?? '');
                if (str_contains($body, 'US-focused')) {
                    $beat['body'] = 'Finished goods are packed for careful transit to your door.';
                }
            }
            unset($beat);
            $config['beats'] = $beats;
            DB::table('homepage_sections')->where('id', $story->id)->update([
                'config' => json_encode($config),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally non-reversible copy cleanup.
    }
};
