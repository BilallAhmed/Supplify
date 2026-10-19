<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCsvService
{
    public const HEADERS = [
        'sku',
        'name',
        'slug',
        'price',
        'sale_price',
        'stock_quantity',
        'low_stock_threshold',
        'is_active',
        'is_featured',
        'short_description',
        'description',
        'benefits',
        'ingredients',
        'directions',
        'warnings',
        'seo_title',
        'seo_description',
        'categories',
        'tags',
    ];

    public function __construct(
        private readonly CatalogService $catalog,
    ) {}

    /**
     * @param  Collection<int, Product>|iterable<Product>  $products
     */
    public function download(iterable $products, string $filename = 'products.csv'): StreamedResponse
    {
        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::HEADERS);

            foreach ($products as $product) {
                fputcsv($out, $this->toRow($product));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function import(UploadedFile $file): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Could not read the uploaded file.']];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['CSV is empty.']];
        }

        $header = array_map(fn ($h) => Str::snake(trim((string) $h)), $header);
        $map = array_flip($header);

        if (! isset($map['sku']) || ! isset($map['name']) || ! isset($map['price'])) {
            fclose($handle);

            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['CSV must include sku, name, and price columns.']];
        }

        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            try {
                $data = $this->mapRow($row, $map);
                $sku = Str::upper(trim((string) $data['sku']));

                if ($sku === '' || trim((string) $data['name']) === '') {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: sku and name are required.";

                    continue;
                }

                $categoryIds = $this->resolveCategoryIds((string) ($data['categories'] ?? ''));
                $payload = [
                    'name' => trim((string) $data['name']),
                    'slug' => trim((string) ($data['slug'] ?? '')) ?: null,
                    'sku' => $sku,
                    'price' => $data['price'],
                    'sale_price' => $data['sale_price'] ?? null,
                    'stock_quantity' => (int) ($data['stock_quantity'] ?? 0),
                    'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 5),
                    'is_active' => $this->toBool($data['is_active'] ?? false),
                    'is_featured' => $this->toBool($data['is_featured'] ?? false),
                    'short_description' => $data['short_description'] ?? null,
                    'description' => $data['description'] ?? null,
                    'benefits' => $data['benefits'] ?? null,
                    'ingredients' => $data['ingredients'] ?? null,
                    'directions' => $data['directions'] ?? null,
                    'warnings' => $data['warnings'] ?? null,
                    'seo_title' => $data['seo_title'] ?? null,
                    'seo_description' => $data['seo_description'] ?? null,
                    'tags' => str_replace('|', ',', (string) ($data['tags'] ?? '')),
                ];

                $existing = Product::withTrashed()->where('sku', $sku)->first();

                DB::transaction(function () use ($existing, $payload, $categoryIds, &$created, &$updated) {
                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $this->catalog->updateProduct($existing, $payload, $categoryIds, [], null);
                        $updated++;
                    } else {
                        $this->catalog->createProduct($payload, $categoryIds, [], null);
                        $created++;
                    }
                });
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Row {$rowNum}: ".$e->getMessage();
            }
        }

        fclose($handle);

        return compact('created', 'updated', 'skipped', 'errors');
    }

    /**
     * @return list<string|int|float|null>
     */
    private function toRow(Product $product): array
    {
        $product->loadMissing(['categories', 'tags']);

        return [
            $product->sku,
            $product->name,
            $product->slug,
            Money::toDecimalString($product->price_cents),
            $product->sale_price_cents !== null ? Money::toDecimalString($product->sale_price_cents) : '',
            $product->stock_quantity,
            $product->low_stock_threshold,
            $product->is_active ? '1' : '0',
            $product->is_featured ? '1' : '0',
            $product->short_description,
            $product->description,
            $product->benefits,
            $product->ingredients,
            $product->directions,
            $product->warnings,
            $product->seo_title,
            $product->seo_description,
            $product->categories->pluck('slug')->implode('|'),
            $product->tags->pluck('name')->implode('|'),
        ];
    }

    /**
     * @param  list<string|null>  $row
     * @param  array<string, int>  $map
     * @return array<string, mixed>
     */
    private function mapRow(array $row, array $map): array
    {
        $data = [];
        foreach (self::HEADERS as $key) {
            $data[$key] = isset($map[$key]) ? ($row[$map[$key]] ?? null) : null;
        }

        return $data;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<int>
     */
    private function resolveCategoryIds(string $raw): array
    {
        $tokens = collect(preg_split('/[|,]/', $raw) ?: [])
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return [];
        }

        return Category::query()
            ->where(function ($q) use ($tokens) {
                $q->whereIn('slug', $tokens)->orWhereIn('name', $tokens);
            })
            ->pluck('id')
            ->all();
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::lower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'on', 'published', 'active'], true);
    }
}
