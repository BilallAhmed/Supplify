<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($couponId),
            ],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required'],
            'min_subtotal' => ['nullable'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = CouponType::tryFrom((string) $this->input('type'));

            if (! $type) {
                return;
            }

            if ($type === CouponType::Percent) {
                $percent = $this->input('value');
                if (! is_numeric($percent) || (int) $percent < 1 || (int) $percent > 100) {
                    $validator->errors()->add('value', 'Percent coupons must be between 1 and 100.');
                }
            } else {
                try {
                    $cents = Money::toCents($this->input('value'));
                    if ($cents < 1) {
                        $validator->errors()->add('value', 'Fixed coupons must be at least $0.01.');
                    }
                } catch (\Throwable) {
                    $validator->errors()->add('value', 'Enter a valid dollar amount.');
                }
            }

            if ($this->filled('min_subtotal')) {
                try {
                    Money::toCents($this->input('min_subtotal'));
                } catch (\Throwable) {
                    $validator->errors()->add('min_subtotal', 'Enter a valid dollar amount.');
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function couponAttributes(): array
    {
        $type = CouponType::from((string) $this->validated('type'));
        $value = $type === CouponType::Percent
            ? (int) $this->input('value')
            : Money::toCents($this->input('value'));

        return [
            'code' => Coupon::normalizeCode((string) $this->validated('code')),
            'type' => $type,
            'value' => $value,
            'min_subtotal_cents' => Money::toCentsNullable($this->input('min_subtotal')),
            'max_uses' => $this->filled('max_uses') ? (int) $this->input('max_uses') : null,
            'starts_at' => $this->input('starts_at') ?: null,
            'ends_at' => $this->input('ends_at') ?: null,
            'is_active' => $this->boolean('is_active'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'code' => is_string($this->input('code'))
                ? Coupon::normalizeCode($this->input('code'))
                : $this->input('code'),
        ]);
    }
}
