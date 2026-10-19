<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['shipping', 'billing', 'both'])],
            'name' => ['required', 'string', 'max:160'],
            'line1' => ['required', 'string', 'max:180'],
            'line2' => ['nullable', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:64'],
            'postal_code' => ['required', 'string', 'max:32'],
            'country' => ['required', 'string', 'size:2', Rule::in(['US'])],
            'phone' => ['nullable', 'string', 'max:32'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'country' => strtoupper((string) ($this->input('country') ?: 'US')),
        ]);
    }
}
