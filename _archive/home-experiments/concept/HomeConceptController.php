<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeConceptController extends Controller
{
    /**
     * Isolated cinematic homepage prototype (Phase 1 — structure + visual direction).
     * Does not replace the live homepage at route('home').
     */
    public function __invoke(): View
    {
        $hero = $this->productBySku('SUP-MAGNESIUM-250MG-90');
        $range = $this->productsBySku([
            'SUP-COQ10-200MG-120',
            'SUP-VITAMIN-B50-COMPLEX-120',
            'SUP-BERBERINE-500MG-120',
        ]);
        $detail = $hero;

        return view('storefront.home-concept', [
            'heroProduct' => $hero,
            'rangeProducts' => $range,
            'detailProduct' => $detail,
            'brand' => [
                'eyebrow' => 'Supplify',
                'headline' => 'Trusted nutrition for everyday routines',
                'lede' => 'Science-led supplements with transparent labels — carefully selected for long-term wellness habits.',
                'intro_title' => 'Built with care, from selection to shelf',
                'intro_body' => 'We bring together thoughtfully chosen nutritional products so customers can understand what they are taking and why it belongs in their routine.',
                'cta_label' => 'Explore Products',
                'cta_url' => route('shop.index'),
            ],
            /**
             * Temporary lifestyle assets (easy to replace).
             * Sources: existing Supplify campaign/lifestyle files under public/images/homepage/.
             * Replace with client photography when available — keep keys stable.
             */
            'lifestyle' => [
                'hero_wash' => 'images/homepage/hero-lifestyle-magnesium.webp',
                'everyday' => 'images/homepage/hero-lifestyle-vitamins.webp',
                'everyday_alt' => 'images/homepage/hero/campaign-daily-vitamins-desktop.webp',
                'closing' => 'images/homepage/hero-lifestyle-coq10.webp',
            ],
            'assetNotes' => [
                'Product packaging: public/images/products/*.png (real Supplify label photography).',
                'Lifestyle scenes: temporary reuse of public/images/homepage/hero-lifestyle-*.webp and campaign-* assets.',
                'No stock photography URLs — all files are local and swappable by path.',
            ],
        ]);
    }

    private function productBySku(string $sku): ?Product
    {
        return Product::query()
            ->where('sku', $sku)
            ->where('is_active', true)
            ->with(['images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')])
            ->first();
    }

    /**
     * @param  list<string>  $skus
     * @return Collection<int, Product>
     */
    private function productsBySku(array $skus): Collection
    {
        $found = Product::query()
            ->whereIn('sku', $skus)
            ->where('is_active', true)
            ->with(['images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')])
            ->get()
            ->keyBy('sku');

        return collect($skus)
            ->map(fn (string $sku) => $found->get($sku))
            ->filter()
            ->values();
    }
}
