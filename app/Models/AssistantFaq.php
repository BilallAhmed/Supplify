<?php

namespace App\Models;

use App\Enums\AssistantIntent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'question',
    'answer',
    'intent',
    'aliases',
    'is_active',
    'sort_order',
])]
class AssistantFaq extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'intent' => AssistantIntent::class,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
