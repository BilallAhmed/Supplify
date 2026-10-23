<?php

namespace Database\Seeders;

use App\Enums\AssistantIntent;
use App\Models\AssistantAlias;
use App\Services\Assistant\AssistantTextNormalizer;
use Illuminate\Database\Seeder;

/**
 * Reviewed starter aliases (English / Roman Urdu / Urdu). Not comprehensive multilingual coverage.
 */
class AssistantAliasSeeder extends Seeder
{
    public function run(): void
    {
        $normalizer = app(AssistantTextNormalizer::class);

        $rows = [
            ['phrase' => 'neend', 'locale' => 'roman_ur', 'maps_to_intent' => AssistantIntent::FindProduct],
            ['phrase' => 'نیند', 'locale' => 'ur', 'maps_to_intent' => AssistantIntent::FindProduct],
            ['phrase' => 'sleep support', 'locale' => 'en', 'maps_to_intent' => AssistantIntent::FindProduct],
            ['phrase' => 'kitne ka hai', 'locale' => 'roman_ur', 'maps_to_intent' => AssistantIntent::PriceOrAvailability],
            ['phrase' => 'قیمت', 'locale' => 'ur', 'maps_to_intent' => AssistantIntent::PriceOrAvailability],
            ['phrase' => 'delivery kab hogi', 'locale' => 'roman_ur', 'maps_to_intent' => AssistantIntent::DeliveryReturnsContact],
            ['phrase' => 'ڈیلیوری', 'locale' => 'ur', 'maps_to_intent' => AssistantIntent::DeliveryReturnsContact],
            ['phrase' => 'vitamin d3', 'locale' => 'en', 'maps_to_intent' => AssistantIntent::IngredientsOrDirections],
            ['phrase' => 'vitamin e', 'locale' => 'en', 'maps_to_intent' => AssistantIntent::IngredientsOrDirections],
        ];

        foreach ($rows as $row) {
            AssistantAlias::query()->updateOrCreate(
                [
                    'normalized_phrase' => $normalizer->normalize($row['phrase']),
                    'locale' => $row['locale'],
                ],
                [
                    'phrase' => $row['phrase'],
                    'maps_to_intent' => $row['maps_to_intent'],
                    'is_active' => true,
                ],
            );
        }
    }
}
