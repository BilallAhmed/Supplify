<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Facades\File;

final class ContactPageContent
{
    public const HERO_IMAGE = 'images/contact/contact-hero.webp';

    /**
     * @return array<string, string>
     */
    public static function enquiryTypes(): array
    {
        return [
            'product_enquiry' => 'Product enquiry',
            'order_support' => 'Order support',
            'business_enquiry' => 'Business enquiry',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(?Page $page = null): array
    {
        return [
            'banner' => [
                'eyebrow' => 'Contact Supplify',
                'heading' => 'Let’s get in touch',
                'intro' => 'Have a question about a product, your order, or Supplify? Send us a message and we’ll help you find the information you need.',
                'image' => self::HERO_IMAGE,
                'image_alt' => 'Contact Supplify — lifestyle banner',
            ],
            'form' => [
                'heading' => 'Send us a message',
                'intro' => 'Share a few details below so we can understand your enquiry.',
                'privacy_note' => 'We’ll use your details to respond to your enquiry. Read our Privacy Policy.',
            ],
            'details' => [
                'heading' => 'Contact details',
                'intro' => 'Prefer to contact us directly? Use the details below.',
                'email' => 'hello@example.com',
                'phone' => '+92 3XX XXXXXXX',
                'location' => 'Pakistan',
                'use_placeholders' => true,
                'placeholder_note' => 'Example contact details. Official details will be updated soon.',
            ],
            'mail' => [
                // Delivery recipient — separate from displayed contact email.
                'notification_recipient' => '',
                'notifications_enabled' => false,
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

        return self::normalize(self::mergeRecursive($defaults, $stored));
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    public static function normalize(array $sections): array
    {
        if (isset($sections['details']) && is_array($sections['details'])) {
            $sections['details']['use_placeholders'] = filter_var(
                $sections['details']['use_placeholders'] ?? true,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if (isset($sections['mail']) && is_array($sections['mail'])) {
            $sections['mail']['notifications_enabled'] = filter_var(
                $sections['mail']['notifications_enabled'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $sections;
    }

    /**
     * Resolve the Contact banner photograph when present under public/.
     *
     * @return array{src: ?string, is_photography: bool, path: string}
     */
    public static function resolveBannerImage(?string $overridePath = null): array
    {
        $candidates = array_filter([
            $overridePath,
            self::HERO_IMAGE,
        ]);

        foreach ($candidates as $relative) {
            $normalized = MediaUrl::normalize((string) $relative);
            if ($normalized === null) {
                continue;
            }

            $url = MediaUrl::toUrl($normalized);
            if ($url && (MediaUrl::absolutePath($normalized) || str_starts_with($normalized, 'http') || File::exists(public_path($normalized)))) {
                return [
                    'src' => $url,
                    'is_photography' => true,
                    'path' => $normalized,
                ];
            }
        }

        return [
            'src' => null,
            'is_photography' => false,
            'path' => self::HERO_IMAGE,
        ];
    }

    /**
     * Whether displayed contact channels may use mailto/tel links.
     *
     * @param  array<string, mixed>  $details
     */
    public static function allowsDirectLinks(array $details): bool
    {
        if (filter_var($details['use_placeholders'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $email = strtolower(trim((string) ($details['email'] ?? '')));
        $phone = trim((string) ($details['phone'] ?? ''));

        if ($email === '' || str_contains($email, 'example.com') || str_contains($email, 'example.org')) {
            return false;
        }

        if ($phone === '' || str_contains($phone, 'X') || str_contains($phone, 'x')) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
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
}
