<?php

namespace App\Models;

use App\Support\ContactPageContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'email',
    'phone',
    'enquiry_type',
    'message',
    'ip_address',
    'user_agent',
    'is_read',
    'read_at',
])]
class ContactEnquiry extends Model
{
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function enquiryTypeLabel(): string
    {
        return ContactPageContent::enquiryTypes()[$this->enquiry_type] ?? $this->enquiry_type;
    }

    public function markRead(): void
    {
        if ($this->is_read) {
            return;
        }

        $this->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }
}
