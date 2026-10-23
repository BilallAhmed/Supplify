<?php

namespace App\Models;

use App\Enums\AssistantSourceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'source_type',
    'source_id',
    'approved_at',
    'approved_by',
])]
class AssistantSourceApproval extends Model
{
    protected function casts(): array
    {
        return [
            'source_type' => AssistantSourceType::class,
            'source_id' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
