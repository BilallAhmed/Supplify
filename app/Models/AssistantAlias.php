<?php

namespace App\Models;

use App\Enums\AssistantIntent;
use App\Enums\AssistantSourceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'phrase',
    'normalized_phrase',
    'locale',
    'maps_to_intent',
    'target_type',
    'target_id',
    'is_active',
])]
class AssistantAlias extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'maps_to_intent' => AssistantIntent::class,
            'target_type' => AssistantSourceType::class,
            'target_id' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
