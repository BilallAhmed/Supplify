<?php

namespace App\Services;

use Illuminate\Http\Request;

class ConsentService
{
    /**
     * @return array{analytics: bool, marketing: bool, decided: bool}
     */
    public function fromRequest(Request $request): array
    {
        $raw = $request->cookie((string) config('tracking.consent_cookie', 'supplify_consent'));

        if (! is_string($raw) || $raw === '') {
            return [
                'analytics' => false,
                'marketing' => false,
                'decided' => false,
            ];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [
                'analytics' => false,
                'marketing' => false,
                'decided' => false,
            ];
        }

        return [
            'analytics' => (bool) ($decoded['analytics'] ?? false),
            'marketing' => (bool) ($decoded['marketing'] ?? false),
            'decided' => true,
        ];
    }

    public function allows(string $category, array $consent, bool $consentRequired): bool
    {
        if (! $consentRequired) {
            return true;
        }

        return match ($category) {
            'analytics' => (bool) ($consent['analytics'] ?? false),
            'marketing' => (bool) ($consent['marketing'] ?? false),
            default => false,
        };
    }
}
