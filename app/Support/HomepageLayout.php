<?php

namespace App\Support;

use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\ContentService;
use Illuminate\Support\Collection;

/** The editor and storefront share one inventory of visible sections and fields. */
final class HomepageLayout
{
    public const ORDER = ['hero', 'shop_by_goal', 'featured_products', 'routine_campaign', 'story', 'daily_picks', 'why_choose_us', 'education', 'newsletter'];

    public static function labels(): array
    {
        return array_combine(self::ORDER, ['Hero slider', 'Shop by Goals', 'Featured Products', 'Wellness banners', 'Our Journey', 'Your Daily Essentials', 'Why Choose Us', 'Learn Before You Buy', 'Newsletter']);
    }

    public static function defaults(string $key): array
    {
        $base = ['key' => $key, 'title' => self::labels()[$key] ?? '', 'subtitle' => null, 'body' => null, 'cta_label' => null, 'cta_url' => null, 'image_path' => null, 'config' => [], 'is_enabled' => true, 'sort_order' => (array_search($key, self::ORDER) + 1) * 10];
        $extra = match ($key) {
            'hero' => ['config' => ['slides' => app(ContentService::class)->approvedHeroSlideDefinitions()]],
            'shop_by_goal' => ['config' => ['button_label' => 'Shop Now', 'goals' => self::goalDefaults()]],
            'featured_products' => ['title' => 'Featured', 'cta_label' => 'Shop All Products', 'cta_url' => '/shop', 'image_path' => 'images/homepage/featured-products.webp', 'config' => ['title_second' => 'Products', 'image_alt' => 'Gentle strength training as part of an everyday wellness routine', 'limit' => 8, 'product_ids' => array_fill(0, 8, null)]],
            'routine_campaign' => ['config' => [
                'left' => ['eyebrow' => 'Small rituals. Everyday care.', 'title' => 'Make wellness', 'title_second' => 'a daily habit.', 'body' => 'Discover essentials for your everyday routine.', 'background' => 'images/homepage/banners/daily-studio.webp', 'element' => 'images/homepage/banners/wellness-essentials.webp', 'product_ids' => self::productIds(['coq10-200mg-120-softgels', 'nadh-20mg-90-tablets', 'rutin-500mg-120-tablets'])],
                'right' => ['eyebrow' => 'Wellness essentials', 'title' => 'Everyday', 'title_second' => 'essentials.', 'body' => "Monolaurin &\nBerberine", 'background' => 'images/homepage/banners/mineral-studio.webp', 'cta_label' => 'Shop Now', 'cta_url' => '/shop', 'product_ids' => self::productIds(['monolaurin-500mg-120-capsules', 'berberine-500mg-120-vegcaps'])],
            ]],
            'story' => ['title' => 'Our journey', 'subtitle' => 'From source to doorstep', 'body' => 'A short look at how we move from ingredient selection to the bottle you receive.', 'cta_label' => 'Discover Our Approach', 'cta_url' => '/pages/about', 'image_path' => 'images/homepage/our-journey.webp', 'config' => ['visual_caption' => 'Formulas shaped for everyday routines', 'beats' => [
                ['label' => '01', 'title' => 'Ingredient selection', 'icon' => 'leaf', 'body' => 'We evaluate ingredients for clarity of purpose and fit within a daily routine.', 'detail' => 'Our review includes the source, available specifications and information needed for a clear label.'],
                ['label' => '02', 'title' => 'Formulation', 'icon' => 'flask', 'body' => 'Formulas are drafted for readable labels and practical serving sizes.', 'detail' => 'We discuss suitable combinations and review the intended role of each ingredient.'],
                ['label' => '03', 'title' => 'Manufacturing & checks', 'icon' => 'shield', 'body' => 'Production partners follow agreed quality checks before products ship.', 'detail' => 'Supplier specifications and supporting documentation guide the questions we ask our partners.'],
                ['label' => '04', 'title' => 'Packaging & delivery', 'icon' => 'package', 'body' => 'Finished goods are packed for careful transit to your door.', 'detail' => 'Readable labels, relevant precautions and thoughtful packing help keep product information close at hand.'],
            ]]],
            'daily_picks' => ['title' => 'Your Daily', 'image_path' => 'images/homepage/daily-essentials.webp', 'config' => ['title_second' => 'Essentials', 'image_alt' => 'An active morning walk as part of an everyday wellness routine', 'product_ids' => array_fill(0, 3, null)]],
            'why_choose_us' => ['subtitle' => 'The Supplify difference', 'title' => 'Why choose us', 'body' => 'Good choices start with clear information. We are building Supplify around thoughtful products, attention to detail and people you can reach.', 'image_path' => 'images/homepage/why-choose-supplify.webp', 'cta_label' => 'Get Started', 'cta_url' => '/pages/contact', 'config' => ['image_alt' => 'A team member carefully reviewing a supplement label before preparing an order', 'contact_prompt' => 'A question before you choose?', 'contact_emphasis' => 'Let’s talk.', 'strip_words' => ['Thoughtful selection', 'Clear labels', 'Everyday wellbeing', 'Here to help'], 'reasons' => [
                ['icon' => 'leaf', 'title' => 'Thoughtful selection', 'body' => 'We consider ingredient forms, product information and how a formula may fit into an everyday routine.'],
                ['icon' => 'focus', 'title' => 'Clear product information', 'body' => 'Find ingredients, serving details and relevant precautions, so you can take a closer look before you choose.'],
                ['icon' => 'package', 'title' => 'Care in the details', 'body' => 'From reviewing labels to preparing orders, we pay attention to the small details along the way.'],
                ['icon' => 'heart', 'title' => 'Here to help', 'body' => 'Have a question about a product or your order? Our team can help you find the information you need.'],
            ]]],
            'education' => ['title' => 'Learn before you buy', 'subtitle' => 'Guides', 'body' => 'Short reads that help you choose with confidence.', 'cta_label' => 'View all articles', 'cta_url' => '/blog', 'config' => ['limit' => 3, 'article_label' => 'Learn More', 'post_ids' => array_fill(0, 3, null)]],
            'newsletter' => ['title' => 'Stay in the loop', 'subtitle' => 'Newsletter', 'body' => 'New formulas, restocks, and practical guidance — no spam.', 'cta_label' => 'Subscribe', 'config' => ['email_label' => 'Email', 'email_placeholder' => 'you@example.com']],
            default => [],
        };

        return array_replace($base, $extra);
    }

    private static function productIds(array $slugs): array
    {
        $ids = Product::query()->whereIn('slug', $slugs)->pluck('id', 'slug');

        return array_map(fn ($slug) => $ids[$slug] ?? null, $slugs);
    }

    private static function goalDefaults(): array
    {
        return Category::query()->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->take(4)->get()->map(fn ($c) => [
            'category_id' => $c->id, 'title' => $c->name, 'body' => $c->description ?: 'Find your everyday essentials.',
            'image' => $c->image_path ?: 'images/homepage/goals/'.$c->slug.'.webp',
        ])->all();
    }

    public static function values(HomepageSection $section): array
    {
        return array_replace_recursive(self::defaults($section->key), $section->only(['title', 'subtitle', 'body', 'cta_label', 'cta_url', 'image_path', 'config', 'is_enabled', 'sort_order']));
    }

    /** Grouped field definitions: no unused generic fields or raw JSON editor. */
    public static function groups(string $key): array
    {
        $groups = [];
        $f = fn ($path, $label, $type = 'text') => compact('path', 'label', 'type');
        $heading = [$f('title', 'Heading'), $f('subtitle', 'Eyebrow'), $f('body', 'Description', 'textarea')];
        $cta = [$f('cta_label', 'Button label'), $f('cta_url', 'Button destination', 'url')];
        $image = [$f('image_path', 'Section image', 'media'), $f('config.image_alt', 'Image description')];
        if ($key === 'hero') {
            for ($i = 0; $i < 3; $i++) {
                $p = "config.slides.$i.";
                $groups['Slide '.($i + 1)] = [$f($p.'eyebrow', 'Eyebrow'), $f($p.'title_accent', 'Green heading line'), $f($p.'title', 'White heading line'), $f($p.'body', 'Description', 'textarea'), $f($p.'cta_label', 'Button label'), $f($p.'cta_url', 'Button destination', 'url'), $f($p.'scene', 'Desktop banner', 'media'), $f($p.'scene_mobile', 'Mobile banner', 'media'), $f($p.'scene_alt', 'Image description')];
            }
        } elseif ($key === 'shop_by_goal') {
            $groups['Section heading'] = [$f('title', 'Heading'), $f('config.button_label', 'Category button label')];
            for ($i = 0; $i < 4; $i++) {
                $p = "config.goals.$i.";
                $groups['Goal '.($i + 1)] = [$f($p.'category_id', 'Linked category', 'category'), $f($p.'title', 'Card heading'), $f($p.'body', 'Short description', 'textarea'), $f($p.'image', 'Category image', 'media')];
            }
        } elseif ($key === 'featured_products') {
            $groups['Promotional card'] = [$f('title', 'Heading — first line'), $f('config.title_second', 'Heading — second line'), ...$image, ...$cta];
            $groups['Products'] = [$f('config.limit', 'Number of products (1–8)', 'number')];
            for ($i = 0; $i < 8; $i++) {
                $groups['Products'][] = $f("config.product_ids.$i", 'Product '.($i + 1), 'product');
            }
        } elseif ($key === 'routine_campaign') {
            foreach (['left' => 3, 'right' => 2] as $side => $count) {
                $p = "config.$side.";
                $fields = [$f($p.'eyebrow', 'Eyebrow'), $f($p.'title', 'Heading — first line'), $f($p.'title_second', 'Heading — second line'), $f($p.'body', 'Description', 'textarea'), $f($p.'background', 'Background image', 'media')];
                if ($side === 'left') {
                    $fields[] = $f($p.'element', 'Moving wellness element (transparent image)', 'media');
                } else {
                    $fields = [...$fields, $f($p.'cta_label', 'Button label'), $f($p.'cta_url', 'Button destination', 'url')];
                }
                for ($i = 0; $i < $count; $i++) {
                    $fields[] = $f($p."product_ids.$i", 'Bottle '.($i + 1), 'banner_product');
                }
                $groups[ucfirst($side).' banner'] = $fields;
            }
        } elseif ($key === 'story') {
            $groups['Introduction'] = [...$heading, $f('image_path', 'Journey image', 'media'), $f('config.visual_caption', 'Image caption'), ...$cta];
            for ($i = 0; $i < 4; $i++) {
                $p = "config.beats.$i.";
                $groups['Step '.($i + 1)] = [$f($p.'label', 'Step number'), $f($p.'icon', 'Icon', 'icon'), $f($p.'title', 'Step heading'), $f($p.'body', 'Main text', 'textarea'), $f($p.'detail', 'Supporting text', 'textarea')];
            }
        } elseif ($key === 'daily_picks') {
            $groups['Heading and lifestyle image'] = [$f('title', 'Heading — first line'), $f('config.title_second', 'Heading — second line'), ...$image];
            for ($i = 0; $i < 3; $i++) {
                $groups['Carousel products'][] = $f("config.product_ids.$i", 'Product '.($i + 1), 'product');
            }
        } elseif ($key === 'why_choose_us') {
            $groups['Introduction'] = [...$heading, ...$image];
            for ($i = 0; $i < 4; $i++) {
                $p = "config.reasons.$i.";
                $groups['Reason '.($i + 1)] = [$f($p.'icon', 'Icon', 'icon'), $f($p.'title', 'Heading'), $f($p.'body', 'Description', 'textarea')];
            }
            for ($i = 0; $i < 4; $i++) {
                $groups['Moving strip'][] = $f("config.strip_words.$i", 'Phrase '.($i + 1));
            }
            $groups['Contact action'] = [$f('config.contact_prompt', 'Prompt'), $f('config.contact_emphasis', 'Emphasized text'), ...$cta];
        } elseif ($key === 'education') {
            $groups['Heading and links'] = [...$heading, ...$cta, $f('config.article_label', 'Article link label'), $f('config.limit', 'Number of articles (1–3)', 'number')];
            for ($i = 0; $i < 3; $i++) {
                $groups['Articles'][] = $f("config.post_ids.$i", 'Article '.($i + 1), 'post');
            }
        } elseif ($key === 'newsletter') {
            $groups['Signup content'] = [...$heading, $f('cta_label', 'Subscribe button label'), $f('config.email_label', 'Email field label'), $f('config.email_placeholder', 'Email placeholder')];
        }

        return $groups;
    }

    public static function fields(string $key): array
    {
        return array_merge(...array_values(self::groups($key)));
    }

    public static function image(?string $path): string
    {
        $url = MediaUrl::toUrl($path) ?? '';
        $absolute = MediaUrl::absolutePath($path);

        return $url.($absolute && is_file($absolute) ? (str_contains($url, '?') ? '&' : '?').'v='.filemtime($absolute) : '');
    }

    public static function products(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));
        $rows = Product::query()->published()->with(['categories', 'tags', 'images', 'variations' => fn ($q) => $q->where('is_enabled',true)])->whereIn('id',$ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $rows->get($id))->filter()->values();
    }
}
