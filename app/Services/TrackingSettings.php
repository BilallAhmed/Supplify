<?php

namespace App\Services;

use App\Models\User;
use App\Support\TrackingIdValidator;
use Illuminate\Support\Str;

class TrackingSettings
{
    public const STORAGE_KEY = 'tracking.config';

    public const GROUP = 'tracking';

    public function __construct(
        private readonly StoreSettings $store,
        private readonly SettingsAuditLogger $audits,
    ) {}

    /**
     * @return array{
     *     environments: list<string>,
     *     consent_required: bool,
     *     gtm: array{enabled: bool, container_id: ?string},
     *     ga4: array{enabled: bool, measurement_id: ?string},
     *     meta: array{enabled: bool, pixel_id: ?string},
     *     custom_snippets: list<array{
     *         id: string,
     *         name: string,
     *         placement: string,
     *         code: string,
     *         enabled: bool,
     *         sort_order: int,
     *         consent_category: string
     *     }>,
     *     updated_at: ?string,
     *     updated_by: ?int
     * }
     */
    public function all(): array
    {
        $stored = $this->store->get(self::STORAGE_KEY);
        $defaults = $this->defaults();

        if (! is_array($stored)) {
            return $defaults;
        }

        return $this->normalize(array_replace_recursive($defaults, $stored));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(array $input, User $actor): array
    {
        $before = $this->all();
        $after = $this->normalize([
            'environments' => $input['environments'] ?? $before['environments'],
            'consent_required' => (bool) ($input['consent_required'] ?? false),
            'gtm' => [
                'enabled' => (bool) ($input['gtm']['enabled'] ?? false),
                'container_id' => TrackingIdValidator::normalizeGtm($input['gtm']['container_id'] ?? null),
            ],
            'ga4' => [
                'enabled' => (bool) ($input['ga4']['enabled'] ?? false),
                'measurement_id' => TrackingIdValidator::normalizeGa4($input['ga4']['measurement_id'] ?? null),
            ],
            'meta' => [
                'enabled' => (bool) ($input['meta']['enabled'] ?? false),
                'pixel_id' => TrackingIdValidator::normalizeMetaPixel($input['meta']['pixel_id'] ?? null),
            ],
            'custom_snippets' => $input['custom_snippets'] ?? [],
            'updated_at' => now()->toIso8601String(),
            'updated_by' => $actor->id,
        ]);

        $this->store->putMany([
            self::STORAGE_KEY => $after,
        ]);

        $this->audits->log(
            self::GROUP,
            'Tracking & Scripts settings updated',
            $this->auditSafe($before),
            $this->auditSafe($after),
            $actor,
        );

        return $after;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'environments' => config('tracking.default_environments', ['production']),
            'consent_required' => true,
            'gtm' => [
                'enabled' => false,
                'container_id' => null,
            ],
            'ga4' => [
                'enabled' => false,
                'measurement_id' => null,
            ],
            'meta' => [
                'enabled' => false,
                'pixel_id' => null,
            ],
            'custom_snippets' => [],
            'updated_at' => null,
            'updated_by' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalize(array $config): array
    {
        $allowedEnvs = config('tracking.allowed_environments', ['production', 'staging', 'local']);
        $environments = array_values(array_unique(array_filter(
            array_map(
                static fn ($env) => strtolower(trim((string) $env)),
                is_array($config['environments'] ?? null) ? $config['environments'] : []
            ),
            static fn (string $env) => in_array($env, $allowedEnvs, true)
        )));

        if ($environments === []) {
            $environments = config('tracking.default_environments', ['production']);
        }

        $snippets = [];
        foreach (is_array($config['custom_snippets'] ?? null) ? $config['custom_snippets'] : [] as $index => $snippet) {
            if (! is_array($snippet)) {
                continue;
            }

            $name = trim((string) ($snippet['name'] ?? ''));
            $code = (string) ($snippet['code'] ?? '');
            $placement = (string) ($snippet['placement'] ?? 'head');
            $category = (string) ($snippet['consent_category'] ?? 'marketing');

            if ($name === '' && trim($code) === '') {
                continue;
            }

            if (! in_array($placement, config('tracking.placements', ['head', 'body_start', 'body_end']), true)) {
                $placement = 'head';
            }

            if (! in_array($category, ['analytics', 'marketing'], true)) {
                $category = 'marketing';
            }

            $snippets[] = [
                'id' => (string) ($snippet['id'] ?? (string) Str::uuid()),
                'name' => Str::limit($name !== '' ? $name : 'Untitled snippet', 120, ''),
                'placement' => $placement,
                'code' => $code,
                'enabled' => (bool) ($snippet['enabled'] ?? false),
                'sort_order' => (int) ($snippet['sort_order'] ?? $index),
                'consent_category' => $category,
            ];
        }

        usort($snippets, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return [
            'environments' => $environments,
            'consent_required' => (bool) ($config['consent_required'] ?? true),
            'gtm' => [
                'enabled' => (bool) ($config['gtm']['enabled'] ?? false),
                'container_id' => TrackingIdValidator::normalizeGtm($config['gtm']['container_id'] ?? null),
            ],
            'ga4' => [
                'enabled' => (bool) ($config['ga4']['enabled'] ?? false),
                'measurement_id' => TrackingIdValidator::normalizeGa4($config['ga4']['measurement_id'] ?? null),
            ],
            'meta' => [
                'enabled' => (bool) ($config['meta']['enabled'] ?? false),
                'pixel_id' => TrackingIdValidator::normalizeMetaPixel($config['meta']['pixel_id'] ?? null),
            ],
            'custom_snippets' => $snippets,
            'updated_at' => isset($config['updated_at']) ? (string) $config['updated_at'] : null,
            'updated_by' => isset($config['updated_by']) ? (int) $config['updated_by'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function auditSafe(array $config): array
    {
        $safe = $config;
        $safe['custom_snippets'] = array_map(static function (array $snippet): array {
            return [
                'id' => $snippet['id'],
                'name' => $snippet['name'],
                'placement' => $snippet['placement'],
                'enabled' => $snippet['enabled'],
                'sort_order' => $snippet['sort_order'],
                'consent_category' => $snippet['consent_category'],
                'code_length' => strlen($snippet['code'] ?? ''),
            ];
        }, $config['custom_snippets'] ?? []);

        return $safe;
    }
}
