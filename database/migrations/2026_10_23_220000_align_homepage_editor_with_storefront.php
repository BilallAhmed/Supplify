<?php

use App\Models\HomepageSection;
use App\Models\Post;
use App\Services\ContentService;
use App\Support\HomepageLayout;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (HomepageLayout::ORDER as $key) {
            $defaults = HomepageLayout::defaults($key);
            $row = HomepageSection::query()->firstOrNew(['key' => $key]);
            if (! $row->exists) {
                $row->fill($defaults)->save();

                continue;
            }
            $config = array_replace_recursive($defaults['config'], $row->config ?? []);
            if ($key === 'hero') {
                $approved = app(ContentService::class)->approvedHeroSlideDefinitions();
                $slides = app(ContentService::class)->heroSlides($row)->map(function ($s, $i) use ($approved) {
                    $s['scene'] = $approved[$i]['scene'];
                    $s['scene_mobile'] = $approved[$i]['scene_mobile'];
                    $s['scene_alt'] = $approved[$i]['scene_alt'];

                    return $s;
                })->all();
                $config = ['slides' => $slides];
            }
            if ($key === 'featured_products') {
                $row->title = 'Featured';
                $row->cta_label = 'Shop All Products';
                $config['limit'] = 8;
            }
            if ($key === 'shop_by_goal') {
                $row->title = 'Shop by Goals';
            }
            if (! $row->image_path && $defaults['image_path']) {
                $row->image_path = $defaults['image_path'];
            }
            $row->config = $config;
            $row->sort_order = $defaults['sort_order'];
            $row->save();
        }
        $content = app(ContentService::class);
        $featured = $content->featuredProducts(8);
        if ($featured->count() < 8) {
            $featured = $featured->concat($content->exploreMoreProducts(8 - $featured->count(), $featured->pluck('id')->all()));
        }
        $daily = $content->exploreMoreProducts(3, $featured->pluck('id')->all());
        foreach (['featured_products' => $featured->pluck('id')->all(), 'daily_picks' => $daily->pluck('id')->all()] as $key => $ids) {
            $row = HomepageSection::where('key', $key)->first();
            $config = $row->config;
            $config['product_ids'] = array_pad($ids, $key === 'featured_products' ? 8 : 3, null);
            $row->config = $config;
            $row->save();
        }
        $education = HomepageSection::where('key', 'education')->first();
        $config = $education->config;
        $config['post_ids'] = array_pad(Post::published()->latest('published_at')->orderByDesc('id')->limit(3)->pluck('id')->all(), 3, null);
        $education->config = $config;
        $education->save();
        HomepageSection::query()->whereNotIn('key', HomepageLayout::ORDER)->update(['is_enabled' => false]);
    }

    public function down(): void
    { /* Retain editable content on rollback. */
    }
};
