<?php

namespace App\Http\Requests\Admin;

use App\Enums\AssistantIntent;
use App\Enums\AssistantSourceType;
use App\Models\AssistantAlias;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssistantAliasRequest extends FormRequest
{
    public function authorize(): bool
    {
        $alias = $this->route('alias');

        if ($alias instanceof AssistantAlias) {
            return $this->user()?->can('update', $alias) ?? false;
        }

        return $this->user()?->can('create', AssistantAlias::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phrase' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:16', Rule::in(['en', 'roman_ur', 'ur'])],
            'maps_to_intent' => ['nullable', Rule::enum(AssistantIntent::class)],
            'target_type' => ['nullable', Rule::enum(AssistantSourceType::class)],
            'target_id' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('target_type');
            $id = $this->input('target_id');

            if (($type && ! $id) || (! $type && $id)) {
                $validator->errors()->add('target_id', 'Target type and target ID must be provided together, or both left blank.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'maps_to_intent' => $this->filled('maps_to_intent') ? $this->input('maps_to_intent') : null,
            'target_type' => $this->filled('target_type') ? $this->input('target_type') : null,
            'target_id' => $this->filled('target_id') ? (int) $this->input('target_id') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function aliasAttributes(): array
    {
        return [
            'phrase' => trim((string) $this->input('phrase')),
            'locale' => (string) $this->input('locale'),
            'maps_to_intent' => $this->input('maps_to_intent'),
            'target_type' => $this->input('target_type'),
            'target_id' => $this->input('target_id'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
