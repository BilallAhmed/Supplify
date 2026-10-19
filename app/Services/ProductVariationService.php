<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariation;
use App\Support\Measurements;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductVariationService
{
    public const MAX_GENERATED = 50;

    public function __construct(
        private readonly MediaService $media,
        private readonly CatalogService $catalog,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $attributesPayload
     * @param  array<int, array<string, mixed>>  $variationsPayload
     */
    public function sync(Product $product, array $attributesPayload, array $variationsPayload): void
    {
        if ($product->product_type !== ProductType::Variable) {
            return;
        }

        DB::transaction(function () use ($product, $attributesPayload, $variationsPayload) {
            $keptAttributeIds = [];
            $valueIdMap = [];

            foreach (array_values($attributesPayload) as $index => $attrData) {
                $name = trim((string) ($attrData['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $slug = Str::slug($name) ?: 'attr-'.($index + 1);
                $attribute = ProductAttribute::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'slug' => $slug,
                    ],
                    [
                        'name' => $name,
                        'sort_order' => $index,
                        'used_for_variations' => $this->toBool($attrData['used_for_variations'] ?? true, true),
                    ]
                );
                $keptAttributeIds[] = $attribute->id;

                $keptValueIds = [];
                $values = $attrData['values'] ?? [];
                if (is_string($values)) {
                    $values = preg_split('/[|,]/', $values) ?: [];
                }

                foreach (array_values((array) $values) as $vIndex => $rawValue) {
                    $valueLabel = trim(is_array($rawValue) ? (string) ($rawValue['value'] ?? '') : (string) $rawValue);
                    if ($valueLabel === '') {
                        continue;
                    }
                    $valueSlug = Str::slug($valueLabel) ?: 'val-'.($vIndex + 1);
                    $value = ProductAttributeValue::query()->updateOrCreate(
                        [
                            'product_attribute_id' => $attribute->id,
                            'slug' => $valueSlug,
                        ],
                        [
                            'value' => $valueLabel,
                            'sort_order' => $vIndex,
                        ]
                    );
                    $keptValueIds[] = $value->id;
                    $valueIdMap[$attribute->id.':'.$valueSlug] = $value->id;
                    $valueIdMap[$attribute->slug.':'.$valueSlug] = $value->id;
                }

                $attribute->values()->whereNotIn('id', $keptValueIds)->delete();
            }

            $product->attributes()->whereNotIn('id', $keptAttributeIds)->each(function (ProductAttribute $attr) {
                $attr->values()->delete();
                $attr->delete();
            });

            $keptVariationIds = [];
            $signatures = [];

            foreach (array_values($variationsPayload) as $row) {
                $optionPairs = $row['options'] ?? [];
                if (! is_array($optionPairs) || $optionPairs === []) {
                    continue;
                }

                $normalized = [];
                $labels = [];
                $optionRows = [];

                foreach ($optionPairs as $attrKey => $valueKey) {
                    $attr = $product->attributes()
                        ->where(fn ($q) => $q->where('id', $attrKey)->orWhere('slug', $attrKey)->orWhere('name', $attrKey))
                        ->first();
                    if (! $attr) {
                        continue;
                    }
                    $value = $attr->values()
                        ->where(fn ($q) => $q->where('id', $valueKey)->orWhere('slug', $valueKey)->orWhere('value', $valueKey))
                        ->first();
                    if (! $value) {
                        continue;
                    }
                    $normalized[$attr->slug] = $value->slug;
                    $labels[$attr->name] = $value->value;
                    $optionRows[] = [$attr->id, $value->id];
                }

                ksort($normalized);
                $signature = collect($normalized)->map(fn ($v, $k) => $k.'='.$v)->implode('|');

                if ($signature === '' || isset($signatures[$signature])) {
                    throw ValidationException::withMessages([
                        'variations' => 'Duplicate variation combinations are not allowed.',
                    ]);
                }
                $signatures[$signature] = true;

                $variation = isset($row['id'])
                    ? ProductVariation::query()->where('product_id', $product->id)->whereKey($row['id'])->first()
                    : null;

                $variation ??= new ProductVariation(['product_id' => $product->id]);

                $sku = trim((string) ($row['sku'] ?? ''));
                if ($sku === '') {
                    $sku = $this->catalog->uniqueSku(
                        $product->sku.'-'.Str::upper(Str::substr(md5($signature), 0, 4)),
                        null,
                        $variation->id
                    );
                } else {
                    $sku = $this->catalog->uniqueSku($sku, null, $variation->id);
                }

                $weightUnit = (string) ($row['weight_unit'] ?? $product->weight_unit ?? 'g');
                $dimensionUnit = (string) ($row['dimension_unit'] ?? $product->dimension_unit ?? 'cm');

                $variation->fill([
                    'sku' => $sku,
                    'is_enabled' => $this->toBool($row['is_enabled'] ?? true, true),
                    'is_default' => $this->toBool($row['is_default'] ?? false, false),
                    'price_cents' => Money::toCents($row['price'] ?? 0),
                    'sale_price_cents' => Money::toCentsNullable($row['sale_price'] ?? null),
                    'sale_starts_at' => $row['sale_starts_at'] ?? null,
                    'sale_ends_at' => $row['sale_ends_at'] ?? null,
                    'manage_stock' => $this->toBool($row['manage_stock'] ?? true, true),
                    'stock_quantity' => (int) ($row['stock_quantity'] ?? 0),
                    'low_stock_threshold' => (int) ($row['low_stock_threshold'] ?? $product->low_stock_threshold ?? 5),
                    'weight_grams' => array_key_exists('weight', $row)
                        ? Measurements::weightToGrams($row['weight'] ?? null, $weightUnit)
                        : $variation->weight_grams,
                    'length_mm' => array_key_exists('length', $row)
                        ? Measurements::dimensionToMm($row['length'] ?? null, $dimensionUnit)
                        : $variation->length_mm,
                    'width_mm' => array_key_exists('width', $row)
                        ? Measurements::dimensionToMm($row['width'] ?? null, $dimensionUnit)
                        : $variation->width_mm,
                    'height_mm' => array_key_exists('height', $row)
                        ? Measurements::dimensionToMm($row['height'] ?? null, $dimensionUnit)
                        : $variation->height_mm,
                    'option_signature' => $signature,
                    'option_labels' => $labels,
                ]);

                if (($row['image'] ?? null) instanceof UploadedFile) {
                    $variation->image_path = $this->media->storeProductImage($row['image'], $product->id);
                }

                $variation->syncStockStatus();
                $variation->save();

                $variation->options()->delete();
                foreach ($optionRows as [$attrId, $valueId]) {
                    $variation->options()->create([
                        'product_attribute_id' => $attrId,
                        'product_attribute_value_id' => $valueId,
                    ]);
                }

                $keptVariationIds[] = $variation->id;
            }

            if (count($signatures) > self::MAX_GENERATED) {
                throw ValidationException::withMessages([
                    'variations' => 'A product can have at most '.self::MAX_GENERATED.' variations.',
                ]);
            }

            $product->variations()->whereNotIn('id', $keptVariationIds)->each(function (ProductVariation $variation) {
                if ($variation->image_path) {
                    $this->media->deletePublicFile($variation->image_path);
                }
                $variation->options()->delete();
                $variation->delete();
            });

            $defaults = $product->variations()->where('is_default', true)->count();
            if ($defaults > 1) {
                $firstDefault = $product->variations()->where('is_default', true)->orderBy('id')->first();
                $product->variations()->where('is_default', true)->where('id', '!=', $firstDefault?->id)->update(['is_default' => false]);
            }

            $product->load('variations');
            $product->syncStockStatus();
            $product->save();
        });
    }

    /**
     * @param  array<int, array{name: string, values: list<string>}>  $attributes
     * @return list<array<string, string>>
     */
    public function previewCombinations(array $attributes): array
    {
        $sets = [];
        foreach ($attributes as $attr) {
            $name = trim((string) ($attr['name'] ?? ''));
            $values = collect($attr['values'] ?? [])
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values()
                ->all();
            if ($name === '' || $values === []) {
                continue;
            }
            $sets[] = ['name' => $name, 'values' => $values];
        }

        if ($sets === []) {
            return [];
        }

        $combinations = [[]];
        foreach ($sets as $set) {
            $next = [];
            foreach ($combinations as $combo) {
                foreach ($set['values'] as $value) {
                    $next[] = $combo + [$set['name'] => $value];
                }
            }
            $combinations = $next;
            if (count($combinations) > self::MAX_GENERATED) {
                throw ValidationException::withMessages([
                    'attributes' => 'Generating combinations would exceed '.self::MAX_GENERATED.' variations. Reduce attribute values.',
                ]);
            }
        }

        return $combinations;
    }

    private function toBool(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
