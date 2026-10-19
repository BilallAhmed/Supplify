<?php

namespace App\Http\Requests\Storefront;

use App\Support\ContactPageContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContactEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'enquiry_type' => ['required', 'string', Rule::in(array_keys(ContactPageContent::enquiryTypes()))],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Discreet honeypot — must stay empty.
            'website' => ['nullable', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website.max' => 'We couldn’t submit your message. Please try again.',
            'message.min' => 'Please include a little more detail in your message.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'phone' => 'phone number',
            'enquiry_type' => 'enquiry type',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (filled($this->input('website'))) {
                $validator->errors()->add('website', 'We couldn’t submit your message. Please try again.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'phone' => filled($this->input('phone')) ? trim((string) $this->input('phone')) : null,
            'message' => trim((string) $this->input('message', '')),
            'website' => (string) $this->input('website', ''),
        ]);
    }
}
