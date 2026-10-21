<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductActivity extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_FEATURED = 'featured';

    public const ACTION_UNFEATURED = 'unfeatured';

    protected $fillable = [
        'product_id',
        'user_id',
        'action',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Product created',
            self::ACTION_UPDATED => 'Product updated',
            self::ACTION_FEATURED => 'Marked as featured',
            self::ACTION_UNFEATURED => 'Removed from featured',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
