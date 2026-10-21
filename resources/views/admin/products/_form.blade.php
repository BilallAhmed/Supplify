@php
    $isEdit = $product->exists;
    $primary = $product->exists ? $product->primaryImage() : null;
@endphp

<form
    method="POST"
    enctype="multipart/form-data"
    action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
    class="space-y-6"
    x-data="productEditor(@js($editorPayload))"
    @keydown.ctrl.s.prevent="$el.requestSubmit()"
>
    @csrf
    @if ($isEdit) @method('PUT') @endif

    @if ($errors->any())
        <div class="rounded-[var(--radius-control)] border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger" role="alert">
            Please fix the highlighted fields. Your entries were preserved.
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
        <div class="min-w-0 space-y-4">
            <div class="admin-tabs" role="tablist" aria-label="Product sections">
                <template x-for="item in tabs" :key="item.id">
                    <button
                        type="button"
                        role="tab"
                        class="admin-tabs__btn"
                        :class="tab === item.id ? 'is-active' : ''"
                        :aria-selected="tab === item.id"
                        @click="tab = item.id"
                        x-text="item.label"
                    ></button>
                </template>
            </div>

            {{-- General --}}
            <x-card class="space-y-4" x-show="tab === 'general'" x-cloak>
                <div class="admin-form__section-head">
                    <h2 class="admin-form__section-title">General information</h2>
                    <p class="admin-form__section-desc">Core catalog fields shown on the storefront and in carts.</p>
                </div>
                <div class="admin-form-grid">
                    <x-input label="Product name" name="name" required class="ui-field--full" :value="old('name', $product->name)" help="Shown on the storefront and in carts." />
                    <x-input label="SKU" name="sku" required :value="old('sku', $product->sku)" help="Must be unique across products and variations." />
                    <x-input label="Slug" name="slug" help="Optional — generated from the name if empty." :value="old('slug', $product->slug)" />
                    <x-select label="Product type" name="product_type" class="ui-field--full" x-model="productType" help="Switching type does not delete saved variations.">
                        <option value="simple" @selected(old('product_type', $product->product_type?->value ?? 'simple') === 'simple')>Simple product</option>
                        <option value="variable" @selected(old('product_type', $product->product_type?->value ?? 'simple') === 'variable')>Variable product</option>
                    </x-select>
                    <x-textarea label="Short description" name="short_description" rows="2" help="Used on cards and under the title.">{{ old('short_description', $product->short_description) }}</x-textarea>
                    <x-textarea label="Full description" name="description" rows="6">{{ old('description', $product->description) }}</x-textarea>
                </div>
            </x-card>

            {{-- Pricing & inventory --}}
            <x-card class="space-y-4" x-show="tab === 'pricing'" x-cloak>
                <div class="admin-form__section-head">
                    <h2 class="admin-form__section-title">Pricing & inventory</h2>
                    <p class="admin-form__section-desc">Catalog price, sale windows, and stock tracking.</p>
                </div>
                <p x-show="isVariable()" x-cloak class="text-sm text-ink-muted">
                    Variation-level price and stock are authoritative for variable products. Parent fields below are catalog defaults only.
                </p>
                <div class="admin-form-grid">
                    <x-input label="Regular price (USD)" name="price" type="number" step="0.01" min="0" :value="old('price', $price)" help="Required for simple products; optional display fallback for variable." />
                    <div x-show="!isVariable()" class="min-w-0">
                        <x-input label="Sale price (USD)" name="sale_price" type="number" step="0.01" min="0" :value="old('sale_price', $salePrice)" help="Optional. Must be lower than regular price." />
                    </div>
                    <template x-if="isVariable()">
                        <input type="hidden" name="sale_price" value="">
                    </template>
                </div>
                <div class="admin-form-grid" x-show="!isVariable()">
                    <x-input label="Sale starts" name="sale_starts_at" type="datetime-local" :value="old('sale_starts_at', optional($product->sale_starts_at)?->format('Y-m-d\TH:i'))" />
                    <x-input label="Sale ends" name="sale_ends_at" type="datetime-local" :value="old('sale_ends_at', optional($product->sale_ends_at)?->format('Y-m-d\TH:i'))" />
                </div>
                <template x-if="!isVariable()">
                    <div class="admin-form-grid">
                        <input type="hidden" name="manage_stock" value="0">
                        <x-checkbox name="manage_stock" value="1" label="Track inventory for this product" :checked="(bool) old('manage_stock', $product->manage_stock ?? true)" />
                        <x-input label="Stock quantity" name="stock_quantity" type="number" min="0" :value="old('stock_quantity', $product->stock_quantity ?? 0)" />
                        <x-input label="Low-stock threshold" name="low_stock_threshold" type="number" min="0" required :value="old('low_stock_threshold', $product->low_stock_threshold ?? 5)" />
                        <input type="hidden" name="allow_backorder" value="0">
                        <x-checkbox name="allow_backorder" value="1" label="Allow backorders when out of stock" :checked="(bool) old('allow_backorder', $product->allow_backorder ?? false)" />
                    </div>
                </template>
                <template x-if="isVariable()">
                    <div class="space-y-3">
                        <x-input label="Low-stock threshold (default for new variations)" name="low_stock_threshold" type="number" min="0" required :value="old('low_stock_threshold', $product->low_stock_threshold ?? 5)" />
                        <input type="hidden" name="stock_quantity" value="0">
                        <input type="hidden" name="manage_stock" value="0">
                        <input type="hidden" name="allow_backorder" value="0">
                    </div>
                </template>
            </x-card>

            {{-- Shipping --}}
            <x-card class="space-y-4" x-show="tab === 'shipping'" x-cloak>
                <div class="admin-form__section-head">
                    <h2 class="admin-form__section-title">Shipping</h2>
                    <p class="admin-form__section-desc">Weight, dimensions, and whether this SKU needs fulfillment.</p>
                </div>
                <div class="admin-form-grid">
                    <input type="hidden" name="requires_shipping" value="0">
                    <x-checkbox
                        name="requires_shipping"
                        value="1"
                        label="This product requires shipping"
                        :checked="(bool) old('requires_shipping', $product->requires_shipping ?? true)"
                        help="Digital / non-shippable items can leave this unchecked."
                    />
                    <x-select label="Shipping class" name="shipping_class" help="Optional class for carrier rules.">
                        <option value="">— None —</option>
                        @foreach ($shippingClasses as $key => $label)
                            <option value="{{ $key }}" @selected(old('shipping_class', $product->shipping_class) === $key)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Weight" name="weight" type="number" step="0.001" min="0" :value="old('weight', $weightValue)" help="Leave blank if unknown. Zero means a genuine zero weight." />
                    <x-select label="Unit" name="weight_unit">
                        @foreach ($weightUnits as $unit)
                            <option value="{{ $unit }}" @selected(old('weight_unit', $weightUnit) === $unit)>{{ $unit }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Length" name="length" type="number" step="0.001" min="0" :value="old('length', $lengthValue)" />
                    <x-input label="Width" name="width" type="number" step="0.001" min="0" :value="old('width', $widthValue)" />
                    <x-input label="Height" name="height" type="number" step="0.001" min="0" :value="old('height', $heightValue)" />
                    <x-select label="Dimension unit" name="dimension_unit">
                        @foreach ($dimensionUnits as $unit)
                            <option value="{{ $unit }}" @selected(old('dimension_unit', $dimensionUnit) === $unit)>{{ $unit }}</option>
                        @endforeach
                    </x-select>
                </div>
                <p class="text-xs text-ink-subtle">Stored as whole grams / millimeters to avoid floating-point drift. Weight-based shipping will not invent rates when weight is missing.</p>
            </x-card>

            {{-- Attributes & variations --}}
            <x-card class="space-y-4" x-show="tab === 'variations'" x-cloak>
                <h2 class="font-display text-lg font-bold text-ink">Attributes & variations</h2>
                <template x-if="!isVariable()">
                    <p class="text-sm text-ink-muted">Switch the product type to <strong>Variable</strong> to manage attributes and variations. Existing variation data is kept if you switch away and back.</p>
                </template>
                <div x-show="isVariable()" x-cloak class="space-y-6">
                    {{-- Keep fields out of POST when simple: disable the whole block --}}
                    <fieldset :disabled="!isVariable()" class="contents">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-ink">Attributes</h3>
                            <x-button type="button" variant="outline" size="sm" @click="addAttribute()">Add attribute</x-button>
                        </div>
                        <template x-for="(attr, index) in attributes" :key="index">
                            <div class="grid gap-3 rounded-[var(--radius-control)] border border-line p-3 sm:grid-cols-[1fr_1.4fr_auto]">
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">Name</label>
                                    <input type="text" class="ui-input mt-1" :name="`attributes[${index}][name]`" x-model="attr.name" placeholder="Size, Strength…">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-ink-muted">Values (comma-separated)</label>
                                    <input type="text" class="ui-input mt-1" :name="`attributes[${index}][values]`" x-model="attr.values" placeholder="30ct, 60ct, 90ct">
                                </div>
                                <div class="flex items-end gap-2">
                                    <label class="flex items-center gap-2 pb-2 text-xs">
                                        <input type="hidden" :name="`attributes[${index}][used_for_variations]`" value="0">
                                        <input type="checkbox" value="1" :name="`attributes[${index}][used_for_variations]`" x-model="attr.used_for_variations" class="size-4 rounded border-line text-brand">
                                        Used for variations
                                    </label>
                                    <button type="button" class="pb-2 text-xs text-danger hover:underline" @click="removeAttribute(index)">Remove</button>
                                </div>
                            </div>
                        </template>
                        <div class="flex flex-wrap gap-2">
                            <x-button type="button" variant="secondary" size="sm" @click="generateVariations()">Generate variations</x-button>
                            <x-button type="button" variant="outline" size="sm" @click="addManualVariation()">Add variation manually</x-button>
                        </div>
                        <p x-show="previewNotice" class="text-sm text-ink-muted" x-text="previewNotice"></p>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-ink">Variations <span class="font-normal text-ink-subtle" x-text="`(${variations.length})`"></span></h3>
                        <template x-if="variations.length === 0">
                            <p class="text-sm text-ink-muted">No variations yet. Generate from attributes or add manually.</p>
                        </template>
                        <template x-for="(variation, vIndex) in variations" :key="vIndex">
                            <div class="rounded-[var(--radius-control)] border border-line">
                                <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                                    <button type="button" class="min-w-0 flex-1 text-left text-sm font-medium text-ink" @click="variation.open = !variation.open">
                                        <span x-text="variationSummary(variation)"></span>
                                    </button>
                                    <div class="flex items-center gap-2 text-xs">
                                        <label class="flex items-center gap-1">
                                            <input type="radio" name="default_variation_ui" :checked="variation.is_default" @change="setDefault(vIndex)">
                                            Default
                                        </label>
                                        <button type="button" class="text-danger hover:underline" @click="removeVariation(vIndex)">Remove</button>
                                    </div>
                                </div>
                                <div x-show="variation.open" x-cloak class="space-y-3 border-t border-line p-3">
                                    <input type="hidden" :name="`variations[${vIndex}][id]`" :value="variation.id || ''">
                                    <input type="hidden" :name="`variations[${vIndex}][is_default]`" :value="variation.is_default ? 1 : 0">
                                    <template x-for="(val, key) in variation.options" :key="key">
                                        <input type="hidden" :name="`variations[${vIndex}][options][${key}]`" :value="val">
                                    </template>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">SKU</label>
                                            <input type="text" class="ui-input mt-1" :name="`variations[${vIndex}][sku]`" x-model="variation.sku">
                                        </div>
                                        <div class="flex items-end gap-4 pb-1">
                                            <label class="flex items-center gap-2 text-xs">
                                                <input type="hidden" :name="`variations[${vIndex}][is_enabled]`" value="0">
                                                <input type="checkbox" value="1" :name="`variations[${vIndex}][is_enabled]`" x-model="variation.is_enabled" class="size-4 rounded border-line text-brand">
                                                Enabled
                                            </label>
                                            <label class="flex items-center gap-2 text-xs">
                                                <input type="hidden" :name="`variations[${vIndex}][manage_stock]`" value="0">
                                                <input type="checkbox" value="1" :name="`variations[${vIndex}][manage_stock]`" x-model="variation.manage_stock" class="size-4 rounded border-line text-brand">
                                                Manage stock
                                            </label>
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">Regular price</label>
                                            <input type="number" step="0.01" min="0" class="ui-input mt-1" :name="`variations[${vIndex}][price]`" x-model="variation.price">
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">Sale price</label>
                                            <input type="number" step="0.01" min="0" class="ui-input mt-1" :name="`variations[${vIndex}][sale_price]`" x-model="variation.sale_price">
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">Stock qty</label>
                                            <input type="number" min="0" class="ui-input mt-1" :name="`variations[${vIndex}][stock_quantity]`" x-model="variation.stock_quantity">
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">Variation image</label>
                                            <input type="file" accept="image/jpeg,image/png,image/webp" class="ui-input mt-1" :name="`variations[${vIndex}][image]`">
                                            <img x-show="variation.image_url" :src="variation.image_url" alt="" class="mt-2 size-14 rounded object-cover">
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-ink-muted">Weight (optional)</label>
                                            <input type="number" step="0.001" min="0" class="ui-input mt-1" :name="`variations[${vIndex}][weight]`" x-model="variation.weight" placeholder="Inherit parent">
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div>
                                                <label class="text-xs font-medium text-ink-muted">L</label>
                                                <input type="number" step="0.001" min="0" class="ui-input mt-1" :name="`variations[${vIndex}][length]`" x-model="variation.length">
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-ink-muted">W</label>
                                                <input type="number" step="0.001" min="0" class="ui-input mt-1" :name="`variations[${vIndex}][width]`" x-model="variation.width">
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-ink-muted">H</label>
                                                <input type="number" step="0.001" min="0" class="ui-input mt-1" :name="`variations[${vIndex}][height]`" x-model="variation.height">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    </fieldset>
                </div>
            </x-card>

            {{-- Specs --}}
            <x-card class="space-y-4" x-show="tab === 'details'" x-cloak>
                <div class="admin-form__section-head">
                    <h2 class="admin-form__section-title">Specifications, benefits & ingredients</h2>
                    <p class="admin-form__section-desc">Editorial product details shown on the product page.</p>
                </div>
                <div class="admin-form-grid">
                    <x-textarea label="Benefits" name="benefits" rows="3">{{ old('benefits', $product->benefits) }}</x-textarea>
                    <x-textarea label="Ingredients" name="ingredients" rows="3">{{ old('ingredients', $product->ingredients) }}</x-textarea>
                    <x-textarea label="Supplement facts / specifications" name="supplement_facts" rows="4">{{ old('supplement_facts', $product->supplement_facts) }}</x-textarea>
                    <x-textarea label="Directions / usage" name="directions" rows="3">{{ old('directions', $product->directions) }}</x-textarea>
                    <x-textarea label="Warnings" name="warnings" rows="3">{{ old('warnings', $product->warnings) }}</x-textarea>
                </div>
            </x-card>

            {{-- Media --}}
            <x-card class="space-y-5" x-show="tab === 'media'" x-cloak>
                <h2 class="font-display text-lg font-bold text-ink">Media</h2>

                <div class="space-y-3">
                    <h3 class="text-sm font-semibold text-ink">Featured image</h3>
                    <p class="text-xs text-ink-muted">Exactly one primary image used on product cards and the main product view.</p>
                    <div class="flex flex-wrap items-start gap-4">
                        <div class="flex size-28 items-center justify-center overflow-hidden rounded-[var(--radius-control)] border border-line bg-brand-mist">
                            <template x-if="featuredUrl">
                                <img :src="featuredUrl" alt="Featured preview" class="size-full object-cover">
                            </template>
                            <template x-if="!featuredUrl">
                                <span class="text-xs text-ink-subtle">No image</span>
                            </template>
                        </div>
                        <div class="space-y-2">
                            <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp" class="ui-input" x-ref="featuredInput" @change="onFeaturedChange($event)">
                            <input type="hidden" name="remove_featured" :value="removeFeatured ? 1 : 0">
                            <x-button type="button" variant="outline" size="sm" @click="clearFeatured()" x-show="featuredUrl">Remove featured</x-button>
                            <p class="text-xs text-ink-subtle">JPG, PNG, or WebP. Max 2MB. Transparency preserved for PNG/WebP.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 border-t border-line pt-5">
                    <h3 class="text-sm font-semibold text-ink">Product gallery</h3>
                    <p class="text-xs text-ink-muted">Additional images. Drag to reorder, or use the move buttons. Edit alt text for accessibility.</p>
                    <input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" class="ui-input" multiple @change="onGalleryChange($event)">
                    <template x-for="(img, index) in gallery" :key="img.id || ('new-'+index)">
                        <div
                            class="flex flex-wrap items-center gap-3 rounded-[var(--radius-control)] border border-line p-3"
                            draggable="true"
                            @dragstart="onDragStart(index)"
                            @dragover.prevent
                            @drop.prevent="onDrop(index)"
                        >
                            <img :src="img.url" alt="" class="size-16 rounded object-cover">
                            <div class="min-w-0 flex-1 space-y-2">
                                <input type="hidden" name="gallery_order[]" :value="img.id || ''" x-show="img.id">
                                <template x-if="img.id">
                                    <input type="text" class="ui-input" :name="`image_alts[${img.id}]`" x-model="img.alt" placeholder="Alt text">
                                </template>
                                <template x-if="!img.id">
                                    <p class="text-xs text-ink-subtle">New upload — alt can be edited after save.</p>
                                </template>
                            </div>
                            <div class="flex flex-col gap-1">
                                <button type="button" class="text-xs text-ink-muted hover:text-ink" @click="moveGallery(index, -1)" aria-label="Move image up">↑</button>
                                <button type="button" class="text-xs text-ink-muted hover:text-ink" @click="moveGallery(index, 1)" aria-label="Move image down">↓</button>
                                <button type="button" class="text-xs text-danger hover:underline" @click="markGalleryRemove(img.id, index)">Remove</button>
                            </div>
                        </div>
                    </template>
                    <template x-for="id in removeGalleryIds" :key="'rm-'+id">
                        <input type="hidden" name="remove_gallery_ids[]" :value="id">
                    </template>
                </div>
            </x-card>

            {{-- SEO --}}
            <x-card class="space-y-4" x-show="tab === 'seo'" x-cloak>
                <div class="admin-form__section-head">
                    <h2 class="admin-form__section-title">SEO</h2>
                    <p class="admin-form__section-desc">Optional search metadata for product landing pages.</p>
                </div>
                <div class="admin-form-grid">
                    <x-input label="SEO title" name="seo_title" :value="old('seo_title', $product->seo_title)" help="Defaults to the product name on the storefront." />
                    <x-textarea label="SEO description" name="seo_description" rows="3">{{ old('seo_description', $product->seo_description) }}</x-textarea>
                </div>
            </x-card>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4 xl:sticky xl:top-4 xl:self-start">
            <x-card class="space-y-4">
                <h2 class="font-display text-lg font-bold text-ink">Publish</h2>
                <input type="hidden" name="is_active" value="0">
                <x-checkbox
                    name="is_active"
                    value="1"
                    label="Published"
                    :checked="(bool) old('is_active', $product->is_active)"
                    help="Draft products stay hidden from the shop."
                />
                <x-select label="Catalog visibility" name="catalog_visibility">
                    @foreach (['visible' => 'Shop & search', 'catalog' => 'Shop only', 'search' => 'Search only', 'hidden' => 'Hidden'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('catalog_visibility', $product->catalog_visibility ?? 'visible') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <div class="admin-form__actions">
                    <x-button type="submit" variant="primary">{{ $isEdit ? 'Update product' : 'Create product' }}</x-button>
                    <x-button href="{{ route('admin.products.index') }}" variant="ghost">Cancel</x-button>
                </div>
            </x-card>

            <x-card class="space-y-3">
                <h2 class="font-display text-base font-bold text-ink">Featured image</h2>
                <div class="overflow-hidden rounded-[var(--radius-control)] border border-line bg-brand-mist">
                    <template x-if="featuredUrl">
                        <img :src="featuredUrl" alt="" class="aspect-square w-full object-cover">
                    </template>
                    <template x-if="!featuredUrl">
                        <div class="flex aspect-square items-center justify-center text-xs text-ink-subtle">None set</div>
                    </template>
                </div>
                <button type="button" class="text-xs font-medium text-brand hover:underline" @click="tab = 'media'">Manage in Media</button>
            </x-card>

            <x-card class="space-y-4">
                <h2 class="font-display text-base font-bold text-ink">Organization</h2>
                @php
                    $categoryPicker = [
                        'items' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
                        'selected' => collect(old('category_ids', $selectedCategoryIds))->map(fn ($id) => (int) $id)->values()->all(),
                        'quickUrl' => route('admin.categories.quick'),
                    ];
                @endphp
                <div x-data="productCategoryPicker(@js($categoryPicker))">
                    <div class="flex items-center justify-between gap-2">
                        <x-label value="Categories" />
                        <a href="{{ route('admin.categories.index') }}" class="text-xs font-medium text-brand hover:underline">Manage all</a>
                    </div>
                    <div class="mt-2 max-h-40 space-y-2 overflow-y-auto rounded-[var(--radius-control)] border border-line p-3">
                        <template x-if="items.length === 0">
                            <p class="text-xs text-ink-muted">No categories yet.</p>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="category_ids[]"
                                    class="size-4 rounded border-line text-brand focus:ring-brand/30"
                                    :value="item.id"
                                    :checked="selected.includes(Number(item.id))"
                                    @change="$event.target.checked
                                        ? selected.push(Number(item.id))
                                        : selected = selected.filter((id) => id !== Number(item.id))"
                                >
                                <span x-text="item.name"></span>
                            </label>
                        </template>
                    </div>
                    <div class="mt-2 flex gap-2">
                        <input type="text" x-model="name" @keydown.enter.prevent="create()" maxlength="160" placeholder="New category…" class="ui-input min-w-0 flex-1">
                        <x-button type="button" variant="outline" size="sm" x-bind:disabled="busy || ! name.trim()" @click="create()">
                            <span x-text="busy ? '…' : 'Add'"></span>
                        </x-button>
                    </div>
                    <p x-cloak x-show="error" class="ui-error mt-1" x-text="error" role="alert"></p>
                </div>

                <x-input label="Tags" name="tags" help="Comma-separated." :value="old('tags', $tagsValue)" />

                <div>
                    <x-label value="Related products" />
                    <div class="mt-2 max-h-40 space-y-2 overflow-y-auto rounded-[var(--radius-control)] border border-line p-3">
                        @forelse ($relatedOptions as $option)
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="related_ids[]"
                                    value="{{ $option->id }}"
                                    class="size-4 rounded border-line text-brand focus:ring-brand/30"
                                    @checked(in_array($option->id, old('related_ids', $selectedRelatedIds), true))
                                >
                                <span>{{ $option->name }} <span class="text-ink-subtle">({{ $option->sku }})</span></span>
                            </label>
                        @empty
                            <p class="text-xs text-ink-muted">Other products appear here after you create more.</p>
                        @endforelse
                    </div>
                </div>
            </x-card>
        </aside>
    </div>
</form>
