<?php

namespace App\Console\Commands;

use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tag;
use App\Services\CatalogService;
use App\Services\MediaLibraryService;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportRealProductsCommand extends Command
{
    protected $signature = 'catalog:import-real-products
                            {--dry-run : Report actions without writing}
                            {--skip-snapshot : Skip recoverable snapshot}
                            {--keep-dummies : Soft-delete dummy products is skipped}';

    protected $description = 'Idempotently import verified Supplify products from public/images/products and archive dummy seed SKUs.';

    /** @var list<string> */
    private const DUMMY_SKUS = [
        'OMG-1000',
        'MAG-NITE',
        'GRN-DAILY',
        'VIT-D3',
        'COL-REN',
        'PRO-BAL',
        'ASH-CALM',
        'CRE-PWR',
        'ZNC-SHLD',
        'BCP-FOC',
        'ELC-HYD',
    ];

    public function __construct(
        private readonly CatalogService $catalog,
        private readonly MediaLibraryService $mediaLibrary,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $inventory = $this->loadInventory();
        $price = (string) config('store.preview_price', '1000.00');
        $priceCents = Money::toCents($price);

        $this->info('Indexing media library for product photographs…');
        if (! $dryRun) {
            $indexed = $this->mediaLibrary->indexExistingAssets();
            $this->line(sprintf(
                'Media index: %d created, %d updated, %d skipped.',
                $indexed['created'],
                $indexed['updated'],
                $indexed['skipped']
            ));
        }

        $report = [
            'ran_at' => now()->toIso8601String(),
            'dry_run' => $dryRun,
            'preview_price' => $price,
            'preview_price_cents' => $priceCents,
            'products' => [],
            'dummy_removed' => [],
            'homepage_updates' => [],
            'unmatched_images' => [],
            'errors' => [],
        ];

        $assignedPaths = [];
        foreach ($inventory as $entry) {
            $assignedPaths[] = $entry['featured'];
            foreach ($entry['gallery'] ?? [] as $path) {
                $assignedPaths[] = $path;
            }
        }
        $assignedPaths = array_values(array_unique($assignedPaths));

        $allProductImages = collect(File::files(public_path('images/products')))
            ->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['png', 'jpg', 'jpeg', 'webp', 'gif'], true))
            ->map(fn ($f) => 'images/products/'.$f->getFilename())
            ->values()
            ->all();

        $report['unmatched_images'] = array_values(array_diff($allProductImages, $assignedPaths));

        if (! $this->option('skip-snapshot') && ! $dryRun) {
            $snapshotPath = $this->writeSnapshot();
            $this->info('Snapshot written: '.$snapshotPath);
            $report['snapshot'] = $snapshotPath;
        }

        if (! $dryRun) {
            DB::transaction(function () use ($inventory, $price, $priceCents, &$report) {
                foreach ($inventory as $entry) {
                    $report['products'][] = $this->upsertProduct($entry, $price, $priceCents);
                }

                if (! $this->option('keep-dummies')) {
                    $report['dummy_removed'] = $this->archiveDummyProducts();
                }

                $report['homepage_updates'] = $this->syncHomepageReferences();
            });
        } else {
            foreach ($inventory as $entry) {
                $report['products'][] = [
                    'sku' => $entry['sku'],
                    'name' => $entry['name'],
                    'action' => Product::withTrashed()->where('sku', $entry['sku'])->exists() ? 'would_update' : 'would_create',
                    'featured' => $entry['featured'],
                    'gallery' => $entry['gallery'] ?? [],
                ];
            }
            $report['dummy_removed'] = Product::query()->whereIn('sku', self::DUMMY_SKUS)->pluck('sku')->all();
        }

        $reportPath = storage_path('app/catalog-imports/report-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->newLine();
        $this->info('Imported/updated products: '.count($report['products']));
        $this->info('Dummy SKUs archived: '.count($report['dummy_removed']));
        $this->info('Unmatched images: '.count($report['unmatched_images']));
        if ($report['unmatched_images'] !== []) {
            foreach ($report['unmatched_images'] as $path) {
                $this->warn('  unmet: '.$path);
            }
        }
        $this->info('Report: '.$reportPath);

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadInventory(): array
    {
        $path = database_path('data/real_products_inventory.php');
        if (! is_file($path)) {
            throw new \RuntimeException('Inventory file missing: '.$path);
        }

        /** @var list<array<string, mixed>> $inventory */
        $inventory = require $path;

        return $inventory;
    }

    private function writeSnapshot(): string
    {
        $products = Product::withTrashed()
            ->with(['categories:id,slug', 'tags:id,slug,name', 'images'])
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'slug' => $p->slug,
                'name' => $p->name,
                'deleted_at' => optional($p->deleted_at)?->toIso8601String(),
                'is_active' => $p->is_active,
                'is_featured' => $p->is_featured,
                'price_cents' => $p->price_cents,
                'sale_price_cents' => $p->sale_price_cents,
                'stock_quantity' => $p->stock_quantity,
                'catalog_visibility' => $p->catalog_visibility,
                'categories' => $p->categories->pluck('slug')->all(),
                'tags' => $p->tags->pluck('slug')->all(),
                'images' => $p->images->map(fn (ProductImage $i) => [
                    'path' => $i->path,
                    'alt' => $i->alt,
                    'sort_order' => $i->sort_order,
                    'is_primary' => $i->is_primary,
                ])->all(),
            ])
            ->all();

        $homepage = HomepageSection::query()->get()->map(fn (HomepageSection $s) => [
            'key' => $s->key,
            'config' => $s->config,
        ])->all();

        $payload = [
            'captured_at' => now()->toIso8601String(),
            'products' => $products,
            'homepage_sections' => $homepage,
        ];

        $path = storage_path('app/catalog-backups/pre-real-import-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function upsertProduct(array $entry, string $price, int $priceCents): array
    {
        foreach (array_merge([$entry['featured']], $entry['gallery'] ?? []) as $path) {
            if (! is_file(public_path($path))) {
                throw new \RuntimeException('Missing product image: '.$path);
            }
            MediaAsset::query()->firstOrCreate(
                ['disk' => 'public_path', 'path' => $path],
                [
                    'original_filename' => basename($path),
                    'title' => pathinfo($path, PATHINFO_FILENAME),
                    'mime_type' => 'image/png',
                    'extension' => pathinfo($path, PATHINFO_EXTENSION),
                    'source' => 'imported',
                    'is_locked' => true,
                ]
            );
        }

        $categoryIds = Category::query()
            ->whereIn('slug', $entry['category_slugs'] ?? [])
            ->pluck('id')
            ->all();

        if ($categoryIds === []) {
            throw new \RuntimeException('No categories resolved for '.$entry['sku']);
        }

        $existing = Product::withTrashed()->where('sku', $entry['sku'])->first();
        $action = $existing ? 'updated' : 'created';

        $data = [
            'name' => $entry['name'],
            'slug' => $entry['slug'] ?? Str::slug($entry['name']),
            'sku' => $entry['sku'],
            'product_type' => ProductType::Simple->value,
            'short_description' => $entry['short_description'] ?? null,
            'description' => $entry['description'] ?? null,
            'benefits' => $entry['benefits'] ?? null,
            'ingredients' => $entry['ingredients'] ?? null,
            'supplement_facts' => $entry['supplement_facts'] ?? null,
            'directions' => $entry['directions'] ?? null,
            'warnings' => $entry['warnings'] ?? null,
            'price' => $price,
            'sale_price' => null,
            'stock_quantity' => 0,
            'manage_stock' => true,
            'allow_backorder' => false,
            'low_stock_threshold' => 0,
            'is_featured' => (bool) ($entry['is_featured'] ?? false),
            'is_active' => true,
            'catalog_visibility' => 'visible',
            'tags' => $entry['tags'] ?? 'temporary-pricing',
            'seo_title' => $entry['name'].' | Supplify',
            'seo_description' => $entry['short_description'] ?? $entry['name'],
        ];

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            // Preserve slug if already unique for this SKU; rewrite to inventory slug when free.
            $slugOwner = Product::withTrashed()
                ->where('slug', $data['slug'])
                ->where('id', '!=', $existing->id)
                ->exists();
            if ($slugOwner) {
                $data['slug'] = $this->catalog->uniqueSlug($data['name'], Product::class, $existing->id);
            }
            $product = $this->catalog->updateProduct($existing, $data, $categoryIds);
        } else {
            if (Product::withTrashed()->where('slug', $data['slug'])->exists()) {
                $data['slug'] = $this->catalog->uniqueSlug($data['name'], Product::class);
            }
            $product = $this->catalog->createProduct($data, $categoryIds);
        }

        $product->price_cents = $priceCents;
        $product->sale_price_cents = null;
        $product->stock_quantity = 0;
        $product->manage_stock = true;
        $product->allow_backorder = false;
        $product->low_stock_threshold = 0;
        $product->stock_status = StockStatus::OutOfStock;
        $product->is_active = true;
        if (! $product->published_at) {
            $product->published_at = now();
        }
        $product->save();

        $this->syncImages($product, $entry);

        // Ensure temporary-pricing tag always present
        $tag = Tag::query()->firstOrCreate(
            ['slug' => 'temporary-pricing'],
            ['name' => 'Temporary pricing']
        );
        $product->tags()->syncWithoutDetaching([$tag->id]);

        if (! empty($entry['is_demo'])) {
            $draft = Tag::query()->firstOrCreate(
                ['slug' => 'editorial-draft'],
                ['name' => 'Editorial draft']
            );
            $product->tags()->syncWithoutDetaching([$draft->id]);
        }

        return [
            'action' => $action,
            'id' => $product->id,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'name' => $product->name,
            'featured' => $entry['featured'],
            'gallery' => $entry['gallery'] ?? [],
            'is_demo' => (bool) ($entry['is_demo'] ?? false),
            'price_cents' => $product->price_cents,
            'purchasable' => $product->fresh()->isPurchasable(),
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function syncImages(Product $product, array $entry): void
    {
        $desired = [];
        $desired[] = [
            'path' => $entry['featured'],
            'is_primary' => true,
            'sort_order' => 0,
            'alt' => $entry['name'].' — front',
        ];

        foreach (array_values($entry['gallery'] ?? []) as $index => $path) {
            if ($path === $entry['featured']) {
                continue;
            }
            $desired[] = [
                'path' => $path,
                'is_primary' => false,
                'sort_order' => $index + 1,
                'alt' => $entry['name'].($index === 0 ? ' — label / back' : ' — additional view'),
            ];
        }

        $desiredPaths = collect($desired)->pluck('path')->all();

        // Remove associations we no longer want (do not delete shared public files).
        $product->images()
            ->whereNotIn('path', $desiredPaths)
            ->each(function (ProductImage $image) {
                $image->delete();
            });

        foreach ($desired as $row) {
            $image = $product->images()->where('path', $row['path'])->first();
            if ($image) {
                $image->update([
                    'is_primary' => $row['is_primary'],
                    'sort_order' => $row['sort_order'],
                    'alt' => $row['alt'],
                ]);
            } else {
                $product->images()->create($row);
            }
        }

        // Ensure only one primary
        $primary = $product->images()->where('path', $entry['featured'])->first();
        if ($primary) {
            $product->images()->where('id', '!=', $primary->id)->update(['is_primary' => false]);
            $primary->update(['is_primary' => true, 'sort_order' => 0]);
        }
    }

    /**
     * @return list<array{id:int,sku:string,name:string}>
     */
    private function archiveDummyProducts(): array
    {
        $removed = [];

        Product::query()
            ->whereIn('sku', self::DUMMY_SKUS)
            ->each(function (Product $product) use (&$removed) {
                $removed[] = [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                ];
                $product->is_active = false;
                $product->is_featured = false;
                $product->save();
                $product->delete();
            });

        return $removed;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function syncHomepageReferences(): array
    {
        $updates = [];
        $map = [
            'OMG-1000' => 'SUP-VITAMIN-E-400IU-120',
            'MAG-NITE' => 'SUP-CANDIDA-SUPPORT-180',
            'GRN-DAILY' => 'SUP-VITAMIN-B50-COMPLEX-120',
        ];

        $hero = HomepageSection::query()->where('key', 'hero')->first();
        if ($hero) {
            $config = $hero->config ?? [];
            $slides = $config['slides'] ?? [];
            $changed = false;
            foreach ($slides as $i => $slide) {
                $sku = $slide['product_sku'] ?? null;
                if ($sku && isset($map[$sku])) {
                    $newSku = $map[$sku];
                    $product = Product::query()->where('sku', $newSku)->first();
                    $slides[$i]['product_sku'] = $newSku;
                    if ($product) {
                        $slides[$i]['cta_url'] = '/products/'.$product->slug;
                    }
                    $changed = true;
                    $updates[] = [
                        'section' => 'hero',
                        'slide' => $i,
                        'from' => $sku,
                        'to' => $newSku,
                    ];
                }
            }
            if ($changed) {
                $config['slides'] = $slides;
                $hero->config = $config;
                $hero->save();
            }
        }

        return $updates;
    }
}
