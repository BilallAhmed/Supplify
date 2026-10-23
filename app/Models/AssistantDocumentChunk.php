<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'assistant_document_id',
    'chunk_index',
    'field_key',
    'content',
    'normalized_content',
])]
class AssistantDocumentChunk extends Model
{
    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(AssistantDocument::class, 'assistant_document_id');
    }
}
