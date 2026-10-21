<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Services\StorefrontCatalogService;
use Illuminate\View\View;

class HomePreviewController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly StorefrontCatalogService $catalog,
    ) {}

    public function __invoke(): View
    {
        $featuredProducts = $this->content->featuredProducts(6);
        $goalCategories = $this->catalog->activeCategories()->take(4);

        $scenes = [
            [
                'key' => 'vitamins',
                'eyebrow' => 'Daily foundation',
                'title' => 'Vitamins that keep pace with you',
                'body' => 'Clear formulas for everyday energy, immunity, and focus — selected with care for long-term routines.',
                'image' => 'images/homepage/hero-lifestyle-vitamins.webp',
                'image_mobile' => 'images/homepage/hero/campaign-daily-vitamins-mobile.webp',
            ],
            [
                'key' => 'minerals',
                'eyebrow' => 'Steady support',
                'title' => 'Minerals for the quieter work',
                'body' => 'Magnesium and essential minerals for recovery, sleep quality, and the hours between training and rest.',
                'image' => 'images/homepage/hero-lifestyle-magnesium.webp',
                'image_mobile' => 'images/homepage/hero/campaign-minerals-mobile.webp',
            ],
            [
                'key' => 'coq10',
                'eyebrow' => 'Cellular care',
                'title' => 'Support that goes deeper',
                'body' => 'Targeted essentials like CoQ10 for heart and cellular energy — transparent labels, science-led choices.',
                'image' => 'images/homepage/hero-lifestyle-coq10.webp',
                'image_mobile' => 'images/homepage/hero/campaign-collection-mobile.webp',
            ],
        ];

        return view('storefront.home-v2', [
            'scenes' => $scenes,
            'featuredProducts' => $featuredProducts,
            'goalCategories' => $goalCategories,
            'heroImage' => 'images/homepage/hero/campaign-collection-desktop.webp',
            'heroImageMobile' => 'images/homepage/hero/campaign-collection-mobile.webp',
        ]);
    }
}
