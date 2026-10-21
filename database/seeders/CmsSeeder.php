<?php

namespace Database\Seeders;

use App\Models\HomepageSection;
use App\Models\Page;
use App\Services\ContentService;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $heroSlides = app(ContentService::class)->defaultHeroSlideDefinitions();
        $heroFirst = $heroSlides[0];

        $sections = [
            [
                'key' => 'hero',
                'title' => $heroFirst['title'],
                'subtitle' => $heroFirst['eyebrow'],
                'body' => $heroFirst['body'],
                'cta_label' => $heroFirst['cta_label'],
                'cta_url' => $heroFirst['cta_url'],
                'image_path' => $heroFirst['scene'],
                'config' => [
                    'slides' => $heroSlides,
                ],
                'is_enabled' => true,
                'sort_order' => 10,
            ],
            [
                'key' => 'trust',
                'title' => 'Shopping with confidence',
                'subtitle' => null,
                'body' => null,
                'cta_label' => null,
                'cta_url' => null,
                'config' => [
                    'items' => [
                        ['title' => 'Transparent labels', 'body' => 'Amounts you can verify at a glance.', 'icon' => 'label'],
                        ['title' => 'Thoughtful forms', 'body' => 'Ingredients chosen for clarity, not hype.', 'icon' => 'flask'],
                        ['title' => 'Careful fulfillment', 'body' => 'Orders prepared for reliable domestic delivery.', 'icon' => 'package'],
                    ],
                ],
                'is_enabled' => true,
                'sort_order' => 20,
            ],
            [
                'key' => 'shop_by_goal',
                'title' => 'Shop by goal',
                'subtitle' => 'Find your lane',
                'body' => 'Browse formulas by the outcomes they support.',
                'cta_label' => null,
                'cta_url' => null,
                'config' => ['limit' => 4],
                'is_enabled' => true,
                'sort_order' => 30,
            ],
            [
                'key' => 'featured_products',
                'title' => 'Featured formulas',
                'subtitle' => 'Staff picks',
                'body' => 'A short list of products we recommend starting with.',
                'cta_label' => 'View All Products',
                'cta_url' => '/shop',
                'config' => ['limit' => 12],
                'is_enabled' => true,
                'sort_order' => 40,
            ],
            [
                'key' => 'explore_more',
                'title' => 'Explore More Formulas',
                'subtitle' => 'From the catalog',
                'body' => 'More published products from the Supplify catalog, ordered by publication date.',
                'cta_label' => 'View All Products',
                'cta_url' => '/shop',
                'config' => ['limit' => 4],
                'is_enabled' => true,
                'sort_order' => 45,
            ],
            [
                'key' => 'story',
                'title' => 'Our journey',
                'subtitle' => 'From source to doorstep',
                'body' => 'A short look at how we move from ingredient selection to the bottle you receive.',
                'cta_label' => 'Discover Our Approach',
                'cta_url' => '/pages/about',
                'image_path' => 'images/homepage/our-journey.webp',
                'config' => [
                    'visual' => 'images/homepage/our-journey.webp',
                    'visual_caption' => 'Formulas shaped for everyday routines',
                    'beats' => [
                        [
                            'label' => '01',
                            'title' => 'Ingredient selection',
                            'body' => 'We evaluate ingredients for clarity of purpose and fit within a daily routine.',
                            'icon' => 'leaf',
                        ],
                        [
                            'label' => '02',
                            'title' => 'Formulation',
                            'body' => 'Formulas are drafted for readable labels and practical serving sizes.',
                            'icon' => 'flask',
                        ],
                        [
                            'label' => '03',
                            'title' => 'Manufacturing & checks',
                            'body' => 'Production partners follow agreed quality checks before products ship.',
                            'icon' => 'shield',
                        ],
                        [
                            'label' => '04',
                            'title' => 'Packaging & delivery',
                            'body' => 'Finished goods are packed for careful transit to your door.',
                            'icon' => 'package',
                        ],
                    ],
                ],
                'is_enabled' => true,
                'sort_order' => 50,
            ],
            [
                'key' => 'education',
                'title' => 'Learn before you buy',
                'subtitle' => 'Guides',
                'body' => 'Short reads that help you choose with confidence.',
                'cta_label' => 'View all articles',
                'cta_url' => '/blog',
                'config' => ['limit' => 3],
                'is_enabled' => true,
                'sort_order' => 60,
            ],
            [
                'key' => 'promo',
                'title' => null,
                'subtitle' => null,
                'body' => null,
                'cta_label' => null,
                'cta_url' => null,
                'config' => [
                    'starts_at' => null,
                    'ends_at' => null,
                ],
                'is_enabled' => false,
                'sort_order' => 70,
            ],
            [
                'key' => 'newsletter',
                'title' => 'Stay in the loop',
                'subtitle' => 'Newsletter',
                'body' => 'New formulas, restocks, and practical guidance — no spam.',
                'cta_label' => 'Subscribe',
                'cta_url' => null,
                'config' => [],
                'is_enabled' => true,
                'sort_order' => 80,
            ],
            [
                'key' => 'testimonials',
                'title' => 'From the community',
                'subtitle' => 'Voices',
                'body' => null,
                'cta_label' => null,
                'cta_url' => null,
                'config' => [
                    'quotes' => [
                        ['quote' => 'Finally a brand that explains what is in the bottle without the hype.', 'author' => 'Jordan M.'],
                        ['quote' => 'The labels are clear and the formulas feel intentional.', 'author' => 'Alex R.'],
                        ['quote' => 'I switched because I could actually compare servings.', 'author' => 'Sam K.'],
                    ],
                ],
                'is_enabled' => false,
                'sort_order' => 990,
            ],
        ];

        foreach ($sections as $section) {
            HomepageSection::query()->updateOrCreate(
                ['key' => $section['key']],
                $section
            );
        }

        // Disable legacy duplicate keys if they still exist.
        HomepageSection::query()
            ->whereIn('key', ['featured', 'categories'])
            ->update(['is_enabled' => false, 'sort_order' => 999]);

        $pages = [
            [
                'title' => 'About',
                'slug' => 'about',
                'body' => "Founded by Muhammad Ahmad, Supplify began with a practical question: how can we bring internationally sourced supplements to Pakistan with the care and clarity customers deserve?\n\nFinding the right products is only part of the answer. Understanding formulations, evaluating suppliers, reviewing documentation, and preparing useful product information all matter.",
                // Structured About layout content is resolved from AboutPageContent defaults
                // and editable in admin.
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'body' => 'Contact form and details are managed in the Contact page sections editor.',
                // Structured Contact layout content is resolved from ContactPageContent defaults.
            ],
            [
                'title' => 'Shipping',
                'slug' => 'shipping',
                'body' => "We currently prioritize US fulfillment.\n\nMost orders ship within 1–2 business days.",
            ],
            [
                'title' => 'Returns',
                'slug' => 'returns',
                'body' => "If something arrives damaged or incorrect, contact us within 14 days.\n\nUnopened products may be eligible for return according to our policy.",
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'body' => "How do I choose a formula?\nStart with your goal, then compare servings and forms on the label.\n\nDo you ship internationally?\nNot yet — we are focused on the US first.",
            ],
            [
                'title' => 'Privacy',
                'slug' => 'privacy',
                'body' => "We collect only what we need to run the store and improve the experience.\n\nWe do not sell personal information.",
            ],
            [
                'title' => 'Terms',
                'slug' => 'terms',
                'body' => "By using this site you agree to our terms of service and policies.\n\nProduct statements have not been evaluated by the FDA.",
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->updateOrCreate(
                ['slug' => $page['slug']],
                [
                    ...$page,
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }
    }
}
