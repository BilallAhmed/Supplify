<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\Tag;
use App\Services\Assistant\AssistantKnowledgeSync;
use App\Support\Measurements;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogService
{
    public function __construct(
        private readonly MediaService $media,
        private readonly ProductActivityLogger $activity,
        private readonly AssistantKnowledgeSync $assistantKnowledge,
    ) {}

    public function createProduct(array $data, array $categoryIds = [], array $relatedIds = [], ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($data, $categoryIds, $relatedIds, $image) {
            $product = new Product($this->mapProductAttributes($data));
            $product->syncStockStatus();
            $this->applyPublishState($product, (bool) ($data['is_active'] ?? false));
            $product->save();

            $this->syncRelations($product, $categoryIds, $relatedIds, $data['tags'] ?? null);

            if ($image) {
                $this->attachImage($product, $image, true);
            }

            $fresh = $product->fresh(['categories', 'tags', 'images', 'relatedProducts']);
            $this->activity->created($fresh);
            // Pivot sync does not emit product updated — refresh assistant knowledge explicitly.
            $this->assistantKnowledge->queueProduct((int) $fresh->id);

            return $fresh;
        });
    }

    public function updateProduct(Product $product, array $data, array $categoryIds = [], array $relatedIds = [], ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($product, $data, $categoryIds, $relatedIds, $image) {
            $product->fill($this->mapProductAttributes($data, $product));
            $product->syncStockStatus();
            $this->applyPublishState($product, (bool) ($data['is_active'] ?? false));
            $product->save();

            $this->syncRelations($product, $categoryIds, $relatedIds, $data['tags'] ?? null);

            if ($image) {
                $this->attachImage($product, $image, $product->images()->doesntExist());
            }

            $fresh = $product->fresh(['categories', 'tags', 'images', 'relatedProducts']);
            $this->activity->updated($fresh);
            $this->assistantKnowledge->queueProduct((int) $fresh->id);

            return $fresh;
        });
    }

    public function duplicateProduct(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $copy = $product->replicate([
                'slug',
                'sku',
                'published_at',
            ]);
            $copy->name = $product->name.' (Copy)';
            $copy->slug = $this->uniqueSlug($product->name.'-copy', Product::class);
            $copy->sku = $this->uniqueSku($product->sku.'-COPY');
            $copy->is_active = false;
            $copy->published_at = null;
            $copy->is_featured = false;
            $copy->syncStockStatus();
            $copy->save();

            $copy->categories()->sync(
                $product->categories->mapWithKeys(fn ($c) => [$c->id => ['sort_order' => $c->pivot->sort_order]])->all()
            );
            $copy->tags()->sync($product->tags->pluck('id'));
            $copy->relatedProducts()->sync(
                $product->relatedProducts->mapWithKeys(fn ($p) => [$p->id => ['type' => $p->pivot->type ?? 'related']])->all()
            );

            foreach ($product->images as $image) {
                $newPath = $this->media->copyPublicFile($image->path, 'products/'.$copy->id);
                $copy->images()->create([
                    'path' => $newPath,
                    'alt' => $image->alt,
                    'sort_order' => $image->sort_order,
                    'is_primary' => $image->is_primary,
                ]);
            }

            $fresh = $copy->fresh(['categories', 'tags', 'images', 'relatedProducts']);
            $this->activity->created($fresh);
            $this->assistantKnowledge->queueProduct((int) $fresh->id);

            return $fresh;
        });
    }

    public function updateInventory(Product $product, int $quantity, ?int $threshold = null): Product
    {
        $product->stock_quantity = max(0, $quantity);

        if ($threshold !== null) {
            $product->low_stock_threshold = max(0, $threshold);
        }

        $product->syncStockStatus();
        $product->save();
        $this->assistantKnowledge->queueProduct((int) $product->id);

        return $product;
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function bulkSetActive(array $ids, bool $active): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return 0;
        }

        $count = 0;

        Product::query()->whereIn('id', $ids)->each(function (Product $product) use ($active, &$count) {
            $this->applyPublishState($product, $active);
            $product->save();
            $this->assistantKnowledge->queueProduct((int) $product->id);
            $count++;
        });

        return $count;
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function bulkSetFeatured(array $ids, bool $featured): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return 0;
        }

        $products = Product::query()->whereIn('id', $ids)->get();
        $changed = 0;

        foreach ($products as $product) {
            if ((bool) $product->is_featured === $featured) {
                continue;
            }

            $product->is_featured = $featured;
            $product->save();
            $this->activity->featuredChanged($product, $featured);
            $changed++;
        }

        return $changed;
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return 0;
        }

        $count = 0;

        Product::query()
            ->with('images')
            ->whereIn('id', $ids)
            ->each(function (Product $product) use (&$count) {
                foreach ($product->images as $image) {
                    // Never delete shared public/images assets referenced by path.
                    if (! str_starts_with(ltrim((string) $image->path, '/'), 'images/')) {
                        $this->media->deletePublicFile($image->path);
                    }
                }
                $productId = (int) $product->id;
                $product->delete();
                $this->assistantKnowledge->queueProduct($productId);
                $count++;
            });

        return $count;
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    public function uniqueSlug(string $name, string $modelClass, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 1;

        while ($modelClass::withTrashed()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function uniqueSku(string $sku, ?int $ignoreProductId = null, ?int $ignoreVariationId = null): string
    {
        $base = Str::upper(Str::slug($sku, '-')) ?: 'SKU';
        $candidate = $base;
        $i = 1;

        while (
            Product::withTrashed()
                ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
                ->where('sku', $candidate)
                ->exists()
            || ProductVariation::query()
                ->when($ignoreVariationId, fn ($q) => $q->where('id', '!=', $ignoreVariationId))
                ->where('sku', $candidate)
                ->exists()
        ) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function mapProductAttributes(array $data, ?Product $existing = null): array
    {
        $name = $data['name'];
        $slugInput = $data['slug'] ?? null;
        $skuInput = $data['sku'];

        $type = ProductType::tryFrom((string) ($data['product_type'] ?? $existing?->product_type?->value ?? 'simple')) ?? ProductType::Simple;
        $weightUnit = (string) ($data['weight_unit'] ?? $existing?->weight_unit ?? 'g');
        $dimensionUnit = (string) ($data['dimension_unit'] ?? $existing?->dimension_unit ?? 'cm');
        $isVariable = $type === ProductType::Variable;

        $attributes = [
            'name' => $name,
            'slug' => $slugInput
                ? $this->uniqueSlug($slugInput, Product::class, $existing?->id)
                : $this->uniqueSlug($name, Product::class, $existing?->id),
            'sku' => $this->uniqueSku($skuInput, $existing?->id),
            'product_type' => $type->value,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'benefits' => $data['benefits'] ?? null,
            'ingredients' => $data['ingredients'] ?? null,
            'supplement_facts' => $data['supplement_facts'] ?? null,
            'directions' => $data['directions'] ?? null,
            'warnings' => $data['warnings'] ?? null,
            'price_cents' => Money::toCents($data['price'] ?? 0),
            'sale_price_cents' => Money::toCentsNullable($data['sale_price'] ?? null),
            'sale_starts_at' => $data['sale_starts_at'] ?? null,
            'sale_ends_at' => $data['sale_ends_at'] ?? null,
            // Variable products own stock on variations — parent inventory is not authoritative.
            'stock_quantity' => $isVariable ? (int) ($existing?->stock_quantity ?? 0) : (int) ($data['stock_quantity'] ?? 0),
            'manage_stock' => $isVariable ? false : (bool) ($data['manage_stock'] ?? true),
            'allow_backorder' => $isVariable ? false : (bool) ($data['allow_backorder'] ?? false),
            'requires_shipping' => array_key_exists('requires_shipping', $data)
                ? (bool) $data['requires_shipping']
                : ($existing?->requires_shipping ?? true),
            'shipping_class' => filled($data['shipping_class'] ?? null) ? trim((string) $data['shipping_class']) : null,
            'weight_unit' => $weightUnit,
            'dimension_unit' => $dimensionUnit,
            'weight_grams' => array_key_exists('weight', $data)
                ? Measurements::weightToGrams($data['weight'], $weightUnit)
                : ($existing?->weight_grams),
            'length_mm' => array_key_exists('length', $data)
                ? Measurements::dimensionToMm($data['length'], $dimensionUnit)
                : ($existing?->length_mm),
            'width_mm' => array_key_exists('width', $data)
                ? Measurements::dimensionToMm($data['width'], $dimensionUnit)
                : ($existing?->width_mm),
            'height_mm' => array_key_exists('height', $data)
                ? Measurements::dimensionToMm($data['height'], $dimensionUnit)
                : ($existing?->height_mm),
            'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 5),
            'catalog_visibility' => (string) ($data['catalog_visibility'] ?? 'visible'),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'stock_status' => StockStatus::OutOfStock,
        ];

        if (array_key_exists('is_featured', $data)) {
            $attributes['is_featured'] = (bool) $data['is_featured'];
        } elseif (! $existing) {
            $attributes['is_featured'] = false;
        }

        return $attributes;
    }

    public function setFeaturedImage(Product $product, ?UploadedFile $file, bool $remove = false): void
    {
        if ($remove) {
            $current = $product->images()->where('is_primary', true)->first();
            if ($current) {
                if (! str_starts_with(ltrim((string) $current->path, '/'), 'images/')) {
                    $this->media->deletePublicFile($current->path);
                }
                $current->delete();
            }

            return;
        }

        if (! $file) {
            return;
        }

        $product->images()->where('is_primary', true)->each(function (ProductImage $image) {
            if (! str_starts_with(ltrim((string) $image->path, '/'), 'images/')) {
                $this->media->deletePublicFile($image->path);
            }
            $image->delete();
        });

        $this->attachImage($product, $file, true);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function addGalleryImages(Product $product, array $files): void
    {
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $this->attachImage($product, $file, false);
            }
        }
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorderImages(Product $product, array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $index => $id) {
            $product->images()->whereKey($id)->update(['sort_order' => $index]);
        }
    }

    public function updateImageAlt(Product $product, int $imageId, ?string $alt): void
    {
        $product->images()->whereKey($imageId)->update(['alt' => $alt]);
    }

    public function detachGalleryImage(Product $product, int $imageId): void
    {
        $image = $product->images()->whereKey($imageId)->where('is_primary', false)->first();
        if (! $image) {
            return;
        }

        // Shared public/images assets stay on disk; only remove the association.
        if (! str_starts_with(ltrim((string) $image->path, '/'), 'images/')) {
            $this->media->deletePublicFile($image->path);
        }
        $image->delete();
    }

    /**
     * @param  array<string, mixed>  $media
     */
    public function syncProductMedia(Product $product, array $media): void
    {
        if (! empty($media['remove_featured'])) {
            $this->setFeaturedImage($product, null, true);
        } elseif (($media['featured'] ?? null) instanceof UploadedFile) {
            $this->setFeaturedImage($product, $media['featured']);
        }

        $galleryFiles = $media['gallery'] ?? [];
        if (is_array($galleryFiles) && $galleryFiles !== []) {
            $this->addGalleryImages($product, $galleryFiles);
        }

        $removeIds = collect($media['remove_gallery_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
        foreach ($removeIds as $id) {
            $this->detachGalleryImage($product, $id);
        }

        $orderedIds = collect($media['gallery_order'] ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();
        if ($orderedIds !== []) {
            $this->reorderImages($product, $orderedIds);
        }

        $alts = $media['image_alts'] ?? [];
        if (is_array($alts)) {
            foreach ($alts as $imageId => $alt) {
                $this->updateImageAlt($product, (int) $imageId, filled($alt) ? (string) $alt : null);
            }
        }
    }

    private function applyPublishState(Product $product, bool $isActive): void
    {
        $product->is_active = $isActive;

        if ($isActive && ! $product->published_at) {
            $product->published_at = now();
        }

        if (! $isActive) {
            // Keep published_at for history; storefront will check is_active.
        }
    }

    private function syncRelations(Product $product, array $categoryIds, array $relatedIds, mixed $tagsInput): void
    {
        $product->categories()->sync(collect($categoryIds)->filter()->mapWithKeys(fn ($id, $i) => [(int) $id => ['sort_order' => $i]])->all());

        $relatedSync = collect($relatedIds)
            ->filter(fn ($id) => (int) $id !== $product->id)
            ->mapWithKeys(fn ($id) => [(int) $id => ['type' => 'related']])
            ->all();
        $product->relatedProducts()->sync($relatedSync);

        $tagNames = collect(explode(',', (string) $tagsInput))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique()
            ->values();

        $tagIds = $tagNames->map(function (string $name) {
            $slug = Str::slug($name) ?: Str::random(6);

            return Tag::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            )->id;
        });

        $product->tags()->sync($tagIds->all());
    }

    private function attachImage(Product $product, UploadedFile $file, bool $asPrimary): void
    {
        $path = $this->media->storeProductImage($file, $product->id);

        if ($asPrimary) {
            $product->images()->update(['is_primary' => false]);
        }

        $product->images()->create([
            'path' => $path,
            'alt' => $product->name,
            'sort_order' => (int) $product->images()->max('sort_order') + 1,
            'is_primary' => $asPrimary || $product->images()->doesntExist(),
        ]);
    }
}
