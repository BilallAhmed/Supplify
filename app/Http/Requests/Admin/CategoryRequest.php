<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->whereNull('deleted_at')),
                Rule::notIn(array_filter([$categoryId])),
            ],
            'description' => ['nullable', 'string'],
            'icon_key' => ['nullable', 'string', 'max:40', Rule::in(Category::GOAL_ICON_KEYS)],
            'icon_path' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'is_active' => $this->boolean('is_active'),
            'icon_key' => filled($this->input('icon_key')) ? $this->input('icon_key') : null,
        ];

        if ($this->exists('icon_path')) {
            $merge['icon_path'] = \App\Support\MediaUrl::normalize($this->input('icon_path'));
        }

        $this->merge($merge);
    }
}
