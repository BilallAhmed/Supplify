<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id ?? $this->route('blog')?->id;

        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::unique('posts', 'slug')->ignore($postId),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'post_category_id' => ['nullable', 'integer', 'exists:post_categories,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'is_published' => $this->boolean('is_published'),
            'post_category_id' => $this->filled('post_category_id') ? $this->input('post_category_id') : null,
        ];

        if ($this->exists('featured_image')) {
            $merge['featured_image'] = \App\Support\MediaUrl::normalize($this->input('featured_image'));
        }

        $this->merge($merge);
    }
}
