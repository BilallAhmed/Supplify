<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TrackingSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowedEnvs = config('tracking.allowed_environments', ['production', 'staging', 'local']);
        $placements = config('tracking.placements', ['head', 'body_start', 'body_end']);
        $maxSnippets = (int) config('tracking.max_custom_snippets', 20);
        $maxBytes = (int) config('tracking.max_snippet_bytes', 50000);

        return [
            'environments' => ['required', 'array', 'min:1'],
            'environments.*' => ['required', 'string', Rule::in($allowedEnvs)],
            'consent_required' => ['sometimes', 'boolean'],
            'gtm_enabled' => ['sometimes', 'boolean'],
            'gtm_container_id' => ['nullable', 'string', 'max:32', 'regex:/^GTM-[A-Z0-9]+$/i'],
            'ga4_enabled' => ['sometimes', 'boolean'],
            'ga4_measurement_id' => ['nullable', 'string', 'max:32', 'regex:/^G-[A-Z0-9]+$/i'],
            'meta_enabled' => ['sometimes', 'boolean'],
            'meta_pixel_id' => ['nullable', 'string', 'max:20', 'regex:/^\d{5,20}$/'],
            'custom_snippets' => ['nullable', 'array', 'max:'.$maxSnippets],
            'custom_snippets.*.id' => ['nullable', 'string', 'max:64'],
            'custom_snippets.*.name' => ['required_with:custom_snippets', 'string', 'max:120'],
            'custom_snippets.*.placement' => ['required_with:custom_snippets', 'string', Rule::in($placements)],
            'custom_snippets.*.code' => ['required_with:custom_snippets', 'string', 'max:'.$maxBytes],
            'custom_snippets.*.enabled' => ['sometimes', 'boolean'],
            'custom_snippets.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'custom_snippets.*.consent_category' => ['required_with:custom_snippets', 'string', Rule::in(['analytics', 'marketing'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gtm_container_id.regex' => 'Enter a valid Google Tag Manager container ID (for example GTM-XXXXXXX).',
            'ga4_measurement_id.regex' => 'Enter a valid GA4 measurement ID (for example G-XXXXXXXXXX).',
            'meta_pixel_id.regex' => 'Enter a valid Meta Pixel ID (digits only).',
            'environments.required' => 'Select at least one environment that may load tracking scripts.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('gtm_enabled') && blank($this->input('gtm_container_id'))) {
                $validator->errors()->add('gtm_container_id', 'A container ID is required when Google Tag Manager is enabled.');
            }

            if ($this->boolean('ga4_enabled') && blank($this->input('ga4_measurement_id'))) {
                $validator->errors()->add('ga4_measurement_id', 'A measurement ID is required when Google Analytics is enabled.');
            }

            if ($this->boolean('meta_enabled') && blank($this->input('meta_pixel_id'))) {
                $validator->errors()->add('meta_pixel_id', 'A Pixel ID is required when Meta Pixel is enabled.');
            }

            foreach ($this->input('custom_snippets', []) as $index => $snippet) {
                $code = (string) ($snippet['code'] ?? '');
                if (preg_match('/<\?(?:php|=)/i', $code)) {
                    $validator->errors()->add(
                        "custom_snippets.{$index}.code",
                        'PHP tags are not allowed in custom snippets.'
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $snippets = $this->input('custom_snippets', []);
        if (is_array($snippets)) {
            $snippets = array_values(array_map(function ($snippet, $index) {
                if (! is_array($snippet)) {
                    return $snippet;
                }

                $snippet['enabled'] = filter_var($snippet['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $snippet['sort_order'] = (int) ($snippet['sort_order'] ?? $index);

                return $snippet;
            }, $snippets, array_keys($snippets)));
        }

        $this->merge([
            'consent_required' => $this->boolean('consent_required'),
            'gtm_enabled' => $this->boolean('gtm_enabled'),
            'ga4_enabled' => $this->boolean('ga4_enabled'),
            'meta_enabled' => $this->boolean('meta_enabled'),
            'gtm_container_id' => $this->filled('gtm_container_id') ? strtoupper(trim((string) $this->input('gtm_container_id'))) : null,
            'ga4_measurement_id' => $this->filled('ga4_measurement_id') ? strtoupper(trim((string) $this->input('ga4_measurement_id'))) : null,
            'meta_pixel_id' => $this->filled('meta_pixel_id') ? trim((string) $this->input('meta_pixel_id')) : null,
            'custom_snippets' => $snippets,
            'environments' => array_values(array_unique(array_filter((array) $this->input('environments', [])))),
        ]);
    }

    /**
     * @return array{
     *     environments: list<string>,
     *     consent_required: bool,
     *     gtm: array{enabled: bool, container_id: ?string},
     *     ga4: array{enabled: bool, measurement_id: ?string},
     *     meta: array{enabled: bool, pixel_id: ?string},
     *     custom_snippets: list<array<string, mixed>>
     * }
     */
    public function trackingPayload(): array
    {
        $data = $this->validated();

        return [
            'environments' => $data['environments'],
            'consent_required' => (bool) ($data['consent_required'] ?? false),
            'gtm' => [
                'enabled' => (bool) ($data['gtm_enabled'] ?? false),
                'container_id' => $data['gtm_container_id'] ?? null,
            ],
            'ga4' => [
                'enabled' => (bool) ($data['ga4_enabled'] ?? false),
                'measurement_id' => $data['ga4_measurement_id'] ?? null,
            ],
            'meta' => [
                'enabled' => (bool) ($data['meta_enabled'] ?? false),
                'pixel_id' => $data['meta_pixel_id'] ?? null,
            ],
            'custom_snippets' => array_values($data['custom_snippets'] ?? []),
        ];
    }
}
