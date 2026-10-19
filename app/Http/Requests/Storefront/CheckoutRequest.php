<?php

namespace App\Http\Requests\Storefront;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $billingRequired = ! $this->boolean('billing_same_as_shipping');

        return [
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'billing_same_as_shipping' => ['sometimes', 'boolean'],

            'shipping_name' => ['required', 'string', 'max:160'],
            'shipping_line1' => ['required', 'string', 'max:180'],
            'shipping_line2' => ['nullable', 'string', 'max:180'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'shipping_state' => ['required', 'string', 'max:64'],
            'shipping_postal_code' => ['required', 'string', 'max:32'],
            'shipping_country' => ['required', 'string', 'size:2', Rule::in(['US'])],
            'shipping_phone' => ['nullable', 'string', 'max:32'],

            'billing_name' => [$billingRequired ? 'required' : 'nullable', 'string', 'max:160'],
            'billing_line1' => [$billingRequired ? 'required' : 'nullable', 'string', 'max:180'],
            'billing_line2' => ['nullable', 'string', 'max:180'],
            'billing_city' => [$billingRequired ? 'required' : 'nullable', 'string', 'max:120'],
            'billing_state' => [$billingRequired ? 'required' : 'nullable', 'string', 'max:64'],
            'billing_postal_code' => [$billingRequired ? 'required' : 'nullable', 'string', 'max:32'],
            'billing_country' => [$billingRequired ? 'required' : 'nullable', 'string', 'size:2', Rule::in(['US'])],
            'billing_phone' => ['nullable', 'string', 'max:32'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'billing_same_as_shipping' => $this->boolean('billing_same_as_shipping', true),
            'payment_method' => $this->input('payment_method') ?: PaymentMethod::Cod->value,
            'shipping_country' => strtoupper((string) ($this->input('shipping_country') ?: 'US')),
            'billing_country' => strtoupper((string) ($this->input('billing_country') ?: 'US')),
        ]);
    }
}
