<?php

namespace App\Models;

use App\Enums\AssistantSourceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'source_type',
    'source_id',
    'title',
    'source_url',
    'searchable_text',
    'content_revision',
    'is_approved',
    'status',
    'meta',
    'source_updated_at',
    'indexed_at',
])]
class AssistantDocument extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_REMOVED = 'removed';

    protected function casts(): array
    {
        return [
            'source_type' => AssistantSourceType::class,
            'source_id' => 'integer',
            'is_approved' => 'boolean',
            'meta' => 'array',
            'source_updated_at' => 'datetime',
            'indexed_at' => 'datetime',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AssistantDocumentChunk::class)->orderBy('chunk_index');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
