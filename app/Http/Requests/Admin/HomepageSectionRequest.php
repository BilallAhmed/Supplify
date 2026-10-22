<?php

namespace App\Http\Requests\Admin;

use App\Support\HomepageLayout;
use App\Support\MediaUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HomepageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $key = $this->route('section')?->key;
        if (! in_array($key, HomepageLayout::ORDER, true)) {
            return ['is_enabled' => ['boolean']];
        }
        $rules = ['is_enabled' => ['sometimes', 'boolean']];
        foreach (HomepageLayout::fields($key) as $f) {
            $rules[$f['path']] = match ($f['type']) {
                'product','banner_product' => [$f['type'] === 'banner_product' ? 'required' : 'nullable', 'integer', Rule::exists('products', 'id')->where(fn ($q) => $q->where('is_active', true)->where('catalog_visibility', '!=', 'hidden')->whereNotNull('published_at')->where('published_at', '<=', now())->whereNull('deleted_at'))],
                'category' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
                'post' => ['nullable', 'integer', Rule::exists('posts', 'id')->where(fn ($q) => $q->where('is_published', true)->where('published_at', '<=', now())->whereNull('deleted_at'))],
                'number' => ['required', 'integer', 'min:1', 'max:'.($key === 'education' ? 3 : 8)],
                'icon' => ['required', Rule::in(['energy', 'sleep', 'shield', 'gut', 'leaf', 'flask', 'heart', 'focus', 'package'])],
                'url' => ['nullable', 'string', 'max:500', function ($a, $v, $fail) {
                    if (! preg_match('~^(https?://[^\s]+|/(?!/)[^\s]*|\#[a-zA-Z][\w-]*)$~', $v)) {
                        $fail('Use a website path such as /shop or a complete http(s) URL.');
                    }
                }],
                'media' => ['nullable', 'string', 'max:500', function ($a, $v, $fail) {
                    if ($v && ! MediaUrl::absolutePath($v)) {
                        $fail('Select an existing image from the media library or upload a new image.');
                    }
                }],
                'textarea' => ['nullable', 'string', 'max:3000'],
                default => ['nullable', 'string', 'max:250'],
            };
        }
        foreach (HomepageLayout::fields($key) as $field) {
            array_unshift($rules[$field['path']], 'present');
        }

        return $rules;
    }

    public function attributes(): array
    {
        $key = $this->route('section')?->key;

        return in_array($key, HomepageLayout::ORDER, true) ? collect(HomepageLayout::fields($key))->pluck('label', 'path')->all() : [];
    }

    public function after(): array
    {
        return [function ($validator) {
            foreach (['config.product_ids', 'config.left.product_ids', 'config.right.product_ids', 'config.post_ids'] as $path) {
                $ids = array_filter((array) $this->input($path, []));
                if (count($ids) !== count(array_unique($ids))) {
                    $validator->errors()->add($path, 'Choose each item only once within this group.');
                }
            }
            $goals = array_filter(array_column((array) $this->input('config.goals', []), 'category_id'));
            if (count($goals) !== count(array_unique($goals))) {
                $validator->errors()->add('config.goals','Choose a different category for each goal.');
            }
        }];
    }
}
