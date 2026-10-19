<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Facades\File;

final class AboutPageContent
{
    /**
     * Photography slots under public/images/about/.
     *
     * @var array<string, array{path: string, ratio: string, fallback_product: ?string}>
     */
    public const SLOTS = [
        'hero' => [
            'path' => 'images/about/about-hero.webp',
            'ratio' => '16 / 9',
            'fallback_product' => null,
        ],
        'story' => [
            'path' => 'images/about/about-story.webp',
            'ratio' => '3 / 2',
            'fallback_product' => null,
        ],
        'journey_research' => [
            'path' => 'images/about/about-journey-research.webp',
            'ratio' => '4 / 3',
            'fallback_product' => null,
        ],
        'journey_partners' => [
            'path' => 'images/about/about-journey-partners.webp',
            'ratio' => '4 / 3',
            'fallback_product' => null,
        ],
        'journey_documentation' => [
            'path' => 'images/about/about-journey-documentation.webp',
            'ratio' => '4 / 3',
            'fallback_product' => null,
        ],
        'journey_packaging' => [
            'path' => 'images/about/about-journey-packaging.webp',
            'ratio' => '4 / 3',
            'fallback_product' => null,
        ],
        'connections' => [
            'path' => 'images/about/about-connections.webp',
            'ratio' => '4 / 3',
            'fallback_product' => null,
        ],
        'everyday' => [
            'path' => 'images/about/about-everyday.webp',
            'ratio' => '16 / 9',
            'fallback_product' => null,
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public static function defaults(?Page $page = null): array
    {
        return [
            'hero' => [
                'enabled' => true,
                'eyebrow' => 'About Supplify',
                'heading' => 'Trusted Nutrition for a Healthier Tomorrow',
                'intro' => 'We are building Supplify to make thoughtfully selected nutritional supplements more accessible in Pakistan. Our approach brings together careful product selection, clear information, and relationships built for the long term.',
                'cta_label' => 'Discover our journey',
                'cta_href' => '#about-approach',
                'image' => 'images/about/about-hero.webp',
                'image_alt' => 'Two people walking and talking along a sunlit garden path, with foliage filling the left side of the frame',
                'focal' => 'right',
            ],
            'purpose' => [
                'enabled' => true,
                'heading' => 'A clear purpose from the beginning',
                'paragraph_1' => 'Founded by Muhammad Ahmad, Supplify began with a practical question: how can we bring internationally sourced supplements to Pakistan with the care and clarity customers deserve?',
                'paragraph_2' => 'Finding the right products is only part of the answer. Understanding formulations, evaluating suppliers, reviewing documentation, and preparing useful product information all matter.',
                'paragraph_3' => 'Our early work has given us experience in supplier evaluation, formulation discussions, packaging development, import planning, and regulatory documentation. Each stage has helped us understand where attention is needed and what it takes to build the brand responsibly.',
                'pull_quote' => '',
                'image' => 'images/about/about-story.webp',
                'image_alt' => 'Editorial illustration of a person reviewing notes and product materials at a desk',
                'focal' => 'center',
            ],
            'principles' => [
                'enabled' => true,
                'heading' => 'What guides our decisions',
                'intro' => '',
                'items' => [
                    [
                        'icon' => 'flask',
                        'title' => 'Quality',
                        'body' => 'We seek manufacturing partners who can provide suitable product specifications and supporting documentation. Asking careful questions helps us make better-informed decisions as we develop our range.',
                    ],
                    [
                        'icon' => 'focus',
                        'title' => 'Transparency',
                        'body' => 'Customers should be able to understand what a product contains, its serving information, and the precautions that matter. We work towards labels and product information that make these details easier to find.',
                    ],
                    [
                        'icon' => 'heart',
                        'title' => 'Consistency',
                        'body' => 'We are building for the long term. Reliable partnerships, attention to detail, and useful customer support are central to the business we want Supplify to become.',
                    ],
                ],
            ],
            'journey' => [
                'enabled' => true,
                'heading' => 'The work behind our range',
                'intro' => 'Before a product becomes part of Supplify, there are questions to explore, conversations to have, and details to work through. Our journey brings these stages together.',
                'stages' => [
                    [
                        'number' => '01',
                        'title' => 'Research and selection',
                        'body' => 'We research ingredients, explore product categories, and consider the role a formulation is intended to play in everyday wellness. Our development interests include gut and liver support, alongside broader nutritional needs.',
                        'image' => 'images/about/about-journey-research.webp',
                        'image_alt' => 'Editorial illustration of research materials and supplement bottles on a work surface',
                        'slot' => 'journey_research',
                    ],
                    [
                        'number' => '02',
                        'title' => 'Partner and formulation discussions',
                        'body' => 'We explore manufacturing options and discuss formulations with potential partners. These conversations help us understand product specifications, packaging requirements, and the documentation available to support each option.',
                        'image' => 'images/about/about-journey-partners.webp',
                        'image_alt' => 'Editorial illustration of a collaborative discussion around formulation documents',
                        'slot' => 'journey_partners',
                    ],
                    [
                        'number' => '03',
                        'title' => 'Documentation and regulatory preparation',
                        'body' => 'For our planned range in Pakistan, we are reviewing the applicable DRAP requirements and preparing the necessary documentation. This work is ongoing and remains an important part of our development process.',
                        'image' => 'images/about/about-journey-documentation.webp',
                        'image_alt' => 'Editorial illustration of organized regulatory and product documentation',
                        'slot' => 'journey_documentation',
                    ],
                    [
                        'number' => '04',
                        'title' => 'Packaging and import coordination',
                        'body' => 'Packaging development and import planning help connect product selection with local operations. Alongside these preparations, we are building the information and customer support needed to introduce our range thoughtfully.',
                        'image' => 'images/about/about-journey-packaging.webp',
                        'image_alt' => 'Editorial illustration of packaging and logistics planning materials',
                        'slot' => 'journey_packaging',
                    ],
                ],
            ],
            'essentials' => [
                'enabled' => true,
                'heading' => 'USA connections. Pakistan focus.',
                'intro' => 'Our journey connects relationships in the USA with the needs of customers in Pakistan.',
                'image' => 'images/about/about-connections.webp',
                'image_alt' => 'Conceptual composite illustrating a USA–Pakistan connection for Supplify’s sourcing and local focus',
                'focal' => 'center',
                'closing' => 'Together, these efforts support our goal of bringing internationally sourced nutrition to Pakistan with a locally informed approach.',
                'blocks' => [
                    [
                        'title' => 'Working with US manufacturers and suppliers',
                        'body' => 'Our USA connections help us explore American supplement manufacturers, potential formulations, manufacturing options, and supporting product documentation.',
                    ],
                    [
                        'title' => 'Building Supplify in Pakistan',
                        'body' => 'Our Pakistan operations focus on developing the brand locally. This includes product selection, packaging, import coordination, regulatory preparation, and customer support.',
                    ],
                ],
            ],
            'everyday' => [
                'enabled' => true,
                'heading' => 'Building for the years ahead',
                'body' => 'We want Supplify to become a trusted name in nutritional supplements in Pakistan. That confidence will depend on the products we develop, the partners we choose, and the way we support our customers.',
                'body_2' => 'We are taking the work step by step, learning from each stage and building on the experience we gain. As our portfolio develops, our aim is to help customers understand their options and the intended role of each product in their routine.',
                'link_label' => '',
                'link_url' => '',
                'image' => 'images/about/about-everyday.webp',
                'image_alt' => 'Editorial lifestyle photograph suggesting everyday wellness and looking ahead',
                'focal' => 'center',
            ],
            'cta' => [
                'enabled' => true,
                'heading' => 'Get to know Supplify',
                'body' => 'Explore our range and learn more about the nutritional products we are bringing together.',
                'primary_label' => 'Explore our range',
                'primary_url' => '/shop',
                'secondary_label' => '',
                'secondary_url' => '',
                'product_images' => [
                    'images/products/1S5A6172.png',
                    'images/products/1S5A6197.png',
                    'images/products/1S5A6174.png',
                ],
            ],
            'team' => [
                'enabled' => false,
                'heading' => 'Our team',
                'intro' => '',
                'members' => [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolve(Page $page): array
    {
        $defaults = self::defaults($page);
        $stored = is_array($page->sections) ? $page->sections : [];

        return self::normalizeFlags(self::migrateLegacy(self::mergeRecursive($defaults, $stored)));
    }

    /**
     * Normalize legacy essentials accordion keys into the connections blocks shape.
     *
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    public static function migrateLegacy(array $sections): array
    {
        if (! isset($sections['essentials']) || ! is_array($sections['essentials'])) {
            return $sections;
        }

        $essentials = &$sections['essentials'];

        if (empty($essentials['image']) && ! empty($essentials['product_image'])) {
            $essentials['image'] = $essentials['product_image'];
        }

        if (empty($essentials['image_alt']) && ! empty($essentials['product_image_alt'])) {
            $essentials['image_alt'] = $essentials['product_image_alt'];
        }

        if (empty($essentials['blocks']) && ! empty($essentials['rows']) && is_array($essentials['rows'])) {
            $essentials['blocks'] = $essentials['rows'];
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    public static function normalizeFlags(array $sections): array
    {
        foreach (['hero', 'purpose', 'principles', 'journey', 'essentials', 'everyday', 'cta', 'team'] as $key) {
            if (! isset($sections[$key]) || ! is_array($sections[$key])) {
                continue;
            }
            if (array_key_exists('enabled', $sections[$key])) {
                $sections[$key]['enabled'] = filter_var($sections[$key]['enabled'], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private static function mergeRecursive(array $defaults, array $stored): array
    {
        foreach ($stored as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key]) && self::isAssoc($defaults[$key])) {
                $defaults[$key] = self::mergeRecursive($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * @param  array<mixed>  $array
     */
    private static function isAssoc(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * @return array{src: ?string, is_photography: bool, fallback_product: ?string, ratio: string, slot: string}
     */
    public static function resolveImage(string $slot, ?string $overridePath = null): array
    {
        $meta = self::SLOTS[$slot] ?? [
            'path' => '',
            'ratio' => '4 / 3',
            'fallback_product' => null,
        ];

        $candidates = array_filter([
            $overridePath,
            $meta['path'] ?? null,
        ]);

        foreach ($candidates as $relative) {
            $normalized = MediaUrl::normalize((string) $relative);
            if ($normalized === null) {
                continue;
            }

            $url = MediaUrl::toUrl($normalized);
            $absolute = MediaUrl::absolutePath($normalized);
            if ($url && ($absolute || str_starts_with($normalized, 'http'))) {
                return [
                    'src' => $url,
                    'is_photography' => true,
                    'fallback_product' => $meta['fallback_product'] ?? null,
                    'ratio' => $meta['ratio'],
                    'slot' => $slot,
                ];
            }

            // Legacy public/images existence check
            $publicAbsolute = public_path(ltrim(str_replace('\\', '/', $normalized), '/'));
            if (File::exists($publicAbsolute)) {
                return [
                    'src' => asset(ltrim(str_replace('\\', '/', $normalized), '/')),
                    'is_photography' => true,
                    'fallback_product' => $meta['fallback_product'] ?? null,
                    'ratio' => $meta['ratio'],
                    'slot' => $slot,
                ];
            }
        }

        return [
            'src' => null,
            'is_photography' => false,
            'fallback_product' => $meta['fallback_product'] ?? null,
            'ratio' => $meta['ratio'],
            'slot' => $slot,
        ];
    }
}
