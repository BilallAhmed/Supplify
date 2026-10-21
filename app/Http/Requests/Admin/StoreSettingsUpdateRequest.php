<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSettingsUpdateRequest extends FormRequest
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
        return [
            'currency' => ['required', 'string', 'size:3'],
            'shipping_method' => ['required', 'string', Rule::in(['flat', 'weight'])],
            'flat_rate' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'free_over' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'tax_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'flat_rate.required' => 'Enter a flat shipping rate.',
            'free_over.required' => 'Enter the free-shipping threshold.',
        ];
    }
}
