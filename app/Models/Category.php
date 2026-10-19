<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'parent_id',
    'name',
    'slug',
    'description',
    'image_path',
    'icon_key',
    'icon_path',
    'is_active',
    'sort_order',
    'seo_title',
    'seo_description',
])]
class Category extends Model
{
    use HasFactory, SoftDeletes;

    public const GOAL_ICON_KEYS = [
        'energy',
        'sleep',
        'shield',
        'gut',
        'leaf',
        'flask',
        'heart',
        'focus',
        'package',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function resolvedIconKey(): string
    {
        if (filled($this->icon_key) && in_array($this->icon_key, self::GOAL_ICON_KEYS, true)) {
            return $this->icon_key;
        }

        return match ($this->slug) {
            'energy-focus' => 'energy',
            'sleep-recovery' => 'sleep',
            'immunity-defense' => 'shield',
            'gut-digestion' => 'gut',
            default => 'leaf',
        };
    }

    public function customIconUrl(): ?string
    {
        return \App\Support\MediaUrl::toUrl($this->icon_path);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
