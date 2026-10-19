<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductType;
use App\Support\Measurements;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        $isVariable = $this->input('product_type') === ProductType::Variable->value;

        return [
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220'],
            'sku' => [
                'required',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->ignore($productId),
                Rule::unique('product_variations', 'sku'),
            ],
            'product_type' => ['required', Rule::in([ProductType::Simple->value, ProductType::Variable->value])],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'ingredients' => ['nullable', 'string'],
            'supplement_facts' => ['nullable', 'string'],
            'directions' => ['nullable', 'string'],
            'warnings' => ['nullable', 'string'],
            'price' => [$isVariable ? 'nullable' : 'required', 'numeric', 'min:0', 'max:999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'stock_quantity' => [$isVariable ? 'nullable' : 'required', 'integer', 'min:0', 'max:1000000'],
            'manage_stock' => ['sometimes', 'boolean'],
            'allow_backorder' => ['sometimes', 'boolean'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'requires_shipping' => ['sometimes', 'boolean'],
            'shipping_class' => ['nullable', 'string', 'max:64', Rule::in(array_keys(config('store.shipping.classes', [])))],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'weight_unit' => ['required', Rule::in(Measurements::weightUnits())],
            'length' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'dimension_unit' => ['required', Rule::in(Measurements::dimensionUnits())],
            'catalog_visibility' => ['required', Rule::in(['visible', 'catalog', 'search', 'hidden'])],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'related_ids' => ['nullable', 'array'],
            'related_ids.*' => ['integer', 'exists:products,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_featured' => ['sometimes', 'boolean'],
            'gallery' => ['nullable', 'array', 'max:20'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gallery_order' => ['nullable', 'array'],
            'gallery_order.*' => ['integer'],
            'remove_gallery_ids' => ['nullable', 'array'],
            'remove_gallery_ids.*' => ['integer'],
            'image_alts' => ['nullable', 'array'],
            'image_alts.*' => ['nullable', 'string', 'max:200'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'attributes' => ['nullable', 'array', 'max:10'],
            'attributes.*.name' => ['nullable', 'string', 'max:80'],
            'attributes.*.values' => ['nullable'],
            'attributes.*.used_for_variations' => ['sometimes', 'boolean'],
            'variations' => ['nullable', 'array', 'max:50'],
            'variations.*.id' => ['nullable', 'integer'],
            'variations.*.sku' => ['nullable', 'string', 'max:64'],
            'variations.*.price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'variations.*.sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'variations.*.stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'variations.*.manage_stock' => ['sometimes', 'boolean'],
            'variations.*.is_enabled' => ['sometimes', 'boolean'],
            'variations.*.is_default' => ['sometimes', 'boolean'],
            'variations.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variations.*.length' => ['nullable', 'numeric', 'min:0'],
            'variations.*.width' => ['nullable', 'numeric', 'min:0'],
            'variations.*.height' => ['nullable', 'numeric', 'min:0'],
            'variations.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'variations.*.options' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $price = $this->input('price');
            $sale = $this->input('sale_price');

            if ($sale !== null && $sale !== '' && is_numeric($price) && is_numeric($sale) && (float) $sale >= (float) $price) {
                $validator->errors()->add('sale_price', 'Sale price must be lower than the regular price.');
            }

            if ($this->boolean('requires_shipping') && $this->input('weight') !== null && $this->input('weight') !== '' && (float) $this->input('weight') < 0) {
                $validator->errors()->add('weight', 'Weight cannot be negative.');
            }

            if ($this->input('product_type') === ProductType::Variable->value) {
                $variations = $this->input('variations', []);
                if (! is_array($variations) || count(array_filter($variations, fn ($row) => ! empty($row['options']))) < 1) {
                    $validator->errors()->add('variations', 'Variable products need at least one variation with attribute options.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'is_active' => $this->boolean('is_active'),
            'allow_backorder' => $this->boolean('allow_backorder'),
            'remove_featured' => $this->boolean('remove_featured'),
            'product_type' => $this->input('product_type', ProductType::Simple->value),
            'weight_unit' => $this->input('weight_unit', 'g'),
            'dimension_unit' => $this->input('dimension_unit', 'cm'),
            'catalog_visibility' => $this->input('catalog_visibility', 'visible'),
            // Absent checkbox = default on for create/legacy clients; form posts "0"/"1" when present.
            'manage_stock' => $this->exists('manage_stock')
                ? $this->boolean('manage_stock')
                : true,
            'requires_shipping' => $this->exists('requires_shipping')
                ? $this->boolean('requires_shipping')
                : true,
        ];

        if ($this->exists('is_featured')) {
            $merge['is_featured'] = $this->boolean('is_featured');
        }

        if ($this->input('shipping_class') === '') {
            $merge['shipping_class'] = null;
        }

        // Preserve legacy image field as featured upload.
        if (! $this->hasFile('featured_image') && $this->hasFile('image')) {
            $this->files->set('featured_image', $this->file('image'));
        }

        $this->merge($merge);
    }

    public function messages(): array
    {
        return [
            'variations.max' => 'A product can have at most 50 variations.',
            'gallery.*.max' => 'Each gallery image must be 2MB or smaller.',
            'featured_image.max' => 'The featured image must be 2MB or smaller.',
        ];
    }
}
