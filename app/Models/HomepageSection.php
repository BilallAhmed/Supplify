<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'title',
    'subtitle',
    'body',
    'cta_label',
    'cta_url',
    'image_path',
    'config',
    'is_enabled',
    'sort_order',
])]
class HomepageSection extends Model
{
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true)->orderBy('sort_order');
    }

    public function label(): string
    {
        return match ($this->key) {
            'hero' => 'Hero',
            'story' => 'Brand story',
            'featured_products' => 'Featured products',
            'shop_by_goal' => 'Shop by goal',
            'promo' => 'Promo banner',
            'trust' => 'Trust / benefits',
            'testimonials' => 'Testimonials',
            'education' => 'Education',
            'newsletter' => 'Newsletter',
            default => str($this->key)->headline()->toString(),
        };
    }
}
