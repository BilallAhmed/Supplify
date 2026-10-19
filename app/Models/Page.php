<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'slug',
    'body',
    'sections',
    'is_published',
    'published_at',
    'seo_title',
    'seo_description',
])]
class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sections' => 'array',
        ];
    }

    public function isAboutPage(): bool
    {
        return $this->slug === 'about';
    }

    public function isContactPage(): bool
    {
        return $this->slug === 'contact';
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
