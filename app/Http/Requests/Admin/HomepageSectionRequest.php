<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HomepageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'body' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
            'is_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'config_json' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'is_enabled' => $this->boolean('is_enabled'),
            'remove_image' => $this->boolean('remove_image'),
        ];

        if ($this->exists('image_path')) {
            $merge['image_path'] = \App\Support\MediaUrl::normalize($this->input('image_path'));
        }

        $this->merge($merge);
    }
}
