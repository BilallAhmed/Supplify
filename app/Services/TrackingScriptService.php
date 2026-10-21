<?php

namespace App\Services;

use App\Support\TrackingIdValidator;
use Illuminate\Http\Request;

class TrackingScriptService
{
    public function __construct(
        private readonly TrackingSettings $settings,
        private readonly TrackingSnippetGenerator $generator,
        private readonly ConsentService $consent,
    ) {}

    /**
     * @return array{
     *     active: bool,
     *     consent_required: bool,
     *     consent: array{analytics: bool, marketing: bool, decided: bool},
     *     show_banner: bool,
     *     gtm_active: bool,
     *     skip_native_due_to_gtm: bool,
     *     head: list<string>,
     *     body_start: list<string>,
     *     body_end: list<string>
     * }
     */
    public function forStorefront(Request $request): array
    {
        $config = $this->settings->all();
        $consent = $this->consent->fromRequest($request);
        $consentRequired = (bool) $config['consent_required'];
        $environmentAllowed = $this->environmentAllowsInjection($config['environments']);

        $empty = [
            'active' => false,
            'consent_required' => $consentRequired,
            'consent' => $consent,
            'show_banner' => $consentRequired && ! $consent['decided'] && $environmentAllowed && ! config('tracking.force_disable'),
            'gtm_active' => false,
            'skip_native_due_to_gtm' => false,
            'head' => [],
            'body_start' => [],
            'body_end' => [],
        ];

        if (config('tracking.force_disable') || ! $environmentAllowed) {
            return $empty;
        }

        $head = [];
        $bodyStart = [];
        $bodyEnd = [];

        $gtmId = $config['gtm']['container_id'];
        $gtmEnabled = (bool) $config['gtm']['enabled']
            && TrackingIdValidator::isValidGtm($gtmId)
            && $this->consent->allows('analytics', $consent, $consentRequired);

        if ($gtmEnabled) {
            $head[] = $this->generator->gtmHead((string) $gtmId);
            $bodyStart[] = $this->generator->gtmBodyStart((string) $gtmId);
        }

        // When GTM is active, GA4 and Meta should be configured inside GTM — not loaded twice.
        $skipNative = $gtmEnabled;

        $ga4Id = $config['ga4']['measurement_id'];
        $ga4Enabled = ! $skipNative
            && (bool) $config['ga4']['enabled']
            && TrackingIdValidator::isValidGa4($ga4Id)
            && $this->consent->allows('analytics', $consent, $consentRequired);

        if ($ga4Enabled) {
            $head[] = $this->generator->ga4Head((string) $ga4Id);
        }

        $metaId = $config['meta']['pixel_id'];
        $metaEnabled = ! $skipNative
            && (bool) $config['meta']['enabled']
            && TrackingIdValidator::isValidMetaPixel($metaId)
            && $this->consent->allows('marketing', $consent, $consentRequired);

        if ($metaEnabled) {
            $head[] = $this->generator->metaPixelHead((string) $metaId);
            $bodyStart[] = $this->generator->metaPixelBodyStart((string) $metaId);
        }

        foreach ($config['custom_snippets'] as $snippet) {
            if (! ($snippet['enabled'] ?? false)) {
                continue;
            }

            $code = trim((string) ($snippet['code'] ?? ''));
            if ($code === '' || $this->containsExecutablePhp($code)) {
                continue;
            }

            $category = (string) ($snippet['consent_category'] ?? 'marketing');
            if (! $this->consent->allows($category, $consent, $consentRequired)) {
                continue;
            }

            $placement = (string) ($snippet['placement'] ?? 'head');
            match ($placement) {
                'body_start' => $bodyStart[] = $code,
                'body_end' => $bodyEnd[] = $code,
                default => $head[] = $code,
            };
        }

        $hasOutput = $head !== [] || $bodyStart !== [] || $bodyEnd !== [];

        return [
            'active' => $hasOutput || ($consentRequired && ! $consent['decided']),
            'consent_required' => $consentRequired,
            'consent' => $consent,
            'show_banner' => $consentRequired && ! $consent['decided'],
            'gtm_active' => $gtmEnabled,
            'skip_native_due_to_gtm' => $skipNative && (
                ((bool) $config['ga4']['enabled'] && TrackingIdValidator::isValidGa4($ga4Id))
                || ((bool) $config['meta']['enabled'] && TrackingIdValidator::isValidMetaPixel($metaId))
            ),
            'head' => $head,
            'body_start' => $bodyStart,
            'body_end' => $bodyEnd,
        ];
    }

    /**
     * @param  list<string>  $environments
     */
    public function environmentAllowsInjection(array $environments): bool
    {
        $current = strtolower((string) app()->environment());

        return in_array($current, array_map('strtolower', $environments), true);
    }

    private function containsExecutablePhp(string $code): bool
    {
        return (bool) preg_match('/<\?(?:php|=)/i', $code);
    }
}
