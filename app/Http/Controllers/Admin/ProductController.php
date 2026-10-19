<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductBulkRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogService;
use App\Services\MediaService;
use App\Services\ProductCsvService;
use App\Services\ProductVariationService;
use App\Support\Measurements;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly MediaService $media,
        private readonly ProductCsvService $csv,
        private readonly ProductVariationService $variations,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->filteredQuery($request)
            ->with(['categories', 'tags', 'images' => fn ($q) => $q->where('is_primary', true)])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->filteredQuery($request)
            ->with(['categories', 'tags'])
            ->latest()
            ->get();

        $stamp = now()->format('Y-m-d_His');

        return $this->csv->download($products, "products-{$stamp}.csv");
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'csv' => ['required', 'file', 'max:5120', 'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel'],
        ]);

        $result = $this->csv->import($request->file('csv'));

        $message = sprintf(
            'Import finished: %d created, %d updated, %d skipped.',
            $result['created'],
            $result['updated'],
            $result['skipped']
        );

        if ($result['errors'] !== []) {
            $preview = collect($result['errors'])->take(5)->implode(' ');

            return redirect()
                ->route('admin.products.index')
                ->with('flash_success', $message)
                ->with('flash_error', $preview.(count($result['errors']) > 5 ? ' …' : ''));
        }

        return redirect()
            ->route('admin.products.index')
            ->with('flash_success', $message);
    }

    public function bulk(ProductBulkRequest $request): RedirectResponse
    {
        $ids = $request->validated('ids');
        $action = $request->validated('action');

        if ($action === 'delete') {
            $this->authorize('delete', Product::class);
        } else {
            $this->authorize('update', Product::class);
        }

        $count = match ($action) {
            'activate' => $this->catalog->bulkSetActive($ids, true),
            'deactivate' => $this->catalog->bulkSetActive($ids, false),
            'feature' => $this->catalog->bulkSetFeatured($ids, true),
            'unfeature' => $this->catalog->bulkSetFeatured($ids, false),
            'delete' => $this->catalog->bulkDelete($ids),
            default => 0,
        };

        return redirect()
            ->route('admin.products.index', $request->only(['q', 'status']))
            ->with('flash_success', $this->bulkMessage($action, $count));
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create', $this->formData(new Product([
            'is_active' => false,
            'is_featured' => false,
            'stock_quantity' => 0,
            'low_stock_threshold' => 5,
            'price_cents' => 0,
            'product_type' => ProductType::Simple,
            'manage_stock' => true,
            'requires_shipping' => true,
            'weight_unit' => 'g',
            'dimension_unit' => 'cm',
            'catalog_visibility' => 'visible',
        ])));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->catalog->createProduct(
            $request->validated(),
            $request->input('category_ids', []),
            $request->input('related_ids', []),
        );

        $this->catalog->syncProductMedia($product, $this->mediaPayload($request));
        $this->variations->sync(
            $product->fresh(),
            $request->input('attributes', []),
            $request->input('variations', [])
        );

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('flash_success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load([
            'categories',
            'tags',
            'images',
            'relatedProducts',
            'attributes.values',
            'variations.options',
        ]);

        return view('admin.products.edit', $this->formData($product));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->catalog->updateProduct(
            $product,
            $request->validated(),
            $request->input('category_ids', []),
            $request->input('related_ids', []),
        );

        $this->catalog->syncProductMedia($product->fresh(), $this->mediaPayload($request));
        $this->variations->sync(
            $product->fresh(),
            $request->input('attributes', []),
            $request->input('variations', [])
        );

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('flash_success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        foreach ($product->images as $image) {
            $this->media->deletePublicFile($image->path);
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('flash_success', 'Product deleted.');
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $this->authorize('duplicate', $product);

        $copy = $this->catalog->duplicateProduct($product);

        return redirect()
            ->route('admin.products.edit', $copy)
            ->with('flash_success', 'Product duplicated as a draft.');
    }

    public function toggleFeatured(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->is_featured = ! $product->is_featured;
        $product->save();

        return redirect()
            ->route('admin.products.index', $request->only(['q', 'status', 'page']))
            ->with('flash_success', $product->is_featured
                ? "“{$product->name}” marked as featured."
                : "“{$product->name}” removed from featured.");
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaPayload(ProductRequest $request): array
    {
        $gallery = $request->file('gallery', []);
        if (! is_array($gallery)) {
            $gallery = $gallery ? [$gallery] : [];
        }

        return [
            'featured' => $request->file('featured_image') ?? $request->file('image'),
            'remove_featured' => $request->boolean('remove_featured'),
            'gallery' => $gallery,
            'gallery_order' => $request->input('gallery_order', []),
            'remove_gallery_ids' => $request->input('remove_gallery_ids', []),
            'image_alts' => $request->input('image_alts', []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Product $product): array
    {
        $weightUnit = old('weight_unit', $product->weight_unit ?: 'g');
        $dimensionUnit = old('dimension_unit', $product->dimension_unit ?: 'cm');

        return [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'relatedOptions' => Product::query()
                ->when($product->exists, fn ($q) => $q->where('id', '!=', $product->id))
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
            'price' => $product->exists ? Money::toDecimalString($product->price_cents) : '',
            'salePrice' => $product->sale_price_cents !== null
                ? Money::toDecimalString($product->sale_price_cents)
                : '',
            'selectedCategoryIds' => $product->exists ? $product->categories->pluck('id')->all() : [],
            'selectedRelatedIds' => $product->exists ? $product->relatedProducts->pluck('id')->all() : [],
            'tagsValue' => $product->exists ? $product->tags->pluck('name')->implode(', ') : '',
            'weightValue' => Measurements::gramsToWeight($product->weight_grams, $weightUnit),
            'lengthValue' => Measurements::mmToDimension($product->length_mm, $dimensionUnit),
            'widthValue' => Measurements::mmToDimension($product->width_mm, $dimensionUnit),
            'heightValue' => Measurements::mmToDimension($product->height_mm, $dimensionUnit),
            'weightUnit' => $weightUnit,
            'dimensionUnit' => $dimensionUnit,
            'shippingClasses' => config('store.shipping.classes', []),
            'weightUnits' => Measurements::weightUnits(),
            'dimensionUnits' => Measurements::dimensionUnits(),
            'editorPayload' => $this->editorPayload($product),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function editorPayload(Product $product): array
    {
        $attributes = old('attributes');
        if (! is_array($attributes)) {
            $attributes = $product->relationLoaded('attributes')
                ? $product->attributes->map(fn ($attr) => [
                    'name' => $attr->name,
                    'values' => $attr->values->pluck('value')->implode(', '),
                    'used_for_variations' => $attr->used_for_variations,
                ])->values()->all()
                : [];
        }

        $variations = old('variations');
        if (! is_array($variations)) {
            $variations = $product->relationLoaded('variations')
                ? $product->variations->map(function ($variation) use ($product) {
                    $unit = $product->weight_unit ?: 'g';
                    $dimUnit = $product->dimension_unit ?: 'cm';

                    return [
                        'id' => $variation->id,
                        'sku' => $variation->sku,
                        'price' => Money::toDecimalString($variation->price_cents),
                        'sale_price' => $variation->sale_price_cents !== null
                            ? Money::toDecimalString($variation->sale_price_cents)
                            : '',
                        'stock_quantity' => $variation->stock_quantity,
                        'manage_stock' => $variation->manage_stock,
                        'is_enabled' => $variation->is_enabled,
                        'is_default' => $variation->is_default,
                        'weight' => Measurements::gramsToWeight($variation->weight_grams, $unit) ?? '',
                        'length' => Measurements::mmToDimension($variation->length_mm, $dimUnit) ?? '',
                        'width' => Measurements::mmToDimension($variation->width_mm, $dimUnit) ?? '',
                        'height' => Measurements::mmToDimension($variation->height_mm, $dimUnit) ?? '',
                        'options' => $variation->option_labels ?? [],
                        'option_signature' => $variation->option_signature,
                        'image_url' => $variation->imageUrl(),
                        'open' => false,
                    ];
                })->values()->all()
                : [];
        }

        $gallery = $product->exists
            ? $product->galleryImages()->map(fn ($img) => [
                'id' => $img->id,
                'url' => $img->url(),
                'alt' => $img->alt,
                'sort_order' => $img->sort_order,
            ])->values()->all()
            : [];

        return [
            'productType' => old('product_type', $product->product_type?->value ?? ProductType::Simple->value),
            'attributes' => $attributes,
            'variations' => $variations,
            'gallery' => $gallery,
            'featuredUrl' => $product->primaryImage()?->url(),
            'maxVariations' => ProductVariationService::MAX_GENERATED,
        ];
    }

    private function filteredQuery(Request $request): Builder
    {
        return Product::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                match ($request->string('status')->toString()) {
                    'active' => $q->where('is_active', true),
                    'draft' => $q->where('is_active', false),
                    'low_stock' => $q->where('stock_status', 'low_stock'),
                    'out_of_stock' => $q->where('stock_status', 'out_of_stock'),
                    default => null,
                };
            });
    }

    private function bulkMessage(string $action, int $count): string
    {
        $label = match ($action) {
            'activate' => 'published',
            'deactivate' => 'set to draft',
            'feature' => 'marked featured',
            'unfeature' => 'unfeatured',
            'delete' => 'deleted',
            default => 'updated',
        };

        return "{$count} product".($count === 1 ? '' : 's')." {$label}.";
    }
}
