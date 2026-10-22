<div
    id="quick-view-root"
    data-lenis-prevent
    x-data="quickViewModal()"
    x-cloak
    @keydown.escape.window="open && close()"
>
    <div
        x-show="open"
        x-transition.opacity
        class="product-qv__backdrop"
        @click="close()"
        aria-hidden="true"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-3"
        class="product-qv"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="open ? 'quick-view-title' : null"
        :aria-hidden="open ? 'false' : 'true'"
        x-ref="dialog"
        @click.stop
        @keydown.tab="trapFocus($event)"
    >
        <button type="button" class="product-qv__close" @click="close()" aria-label="Close quick view" x-ref="closeBtn">
            <svg viewBox="0 0 20 20" width="20" height="20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
            </svg>
        </button>

        <div class="product-qv__inner" x-show="!loading && product" x-cloak>
            <div class="product-qv__gallery">
                <div class="product-qv__stage">
                    <img
                        :src="activeImage?.url"
                        :alt="activeImage?.alt || product?.name"
                        class="product-qv__image"
                        width="480"
                        height="480"
                    >
                </div>
                <template x-if="product?.gallery?.length > 1">
                    <div class="product-qv__thumbs" role="list">
                        <template x-for="(image, index) in product.gallery" :key="image.url + index">
                            <button
                                type="button"
                                class="product-qv__thumb"
                                :class="{ 'is-active': galleryIndex === index }"
                                @click="galleryIndex = index"
                                :aria-label="'View image ' + (index + 1)"
                                role="listitem"
                            >
                                <img :src="image.url" :alt="image.alt || ''" width="64" height="64">
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            <div class="product-qv__details">
                <p class="product-qv__eyebrow" x-text="product?.category || ''" x-show="product?.category"></p>
                <h2 id="quick-view-title" class="product-qv__title font-display" x-text="product?.name"></h2>
                <p class="product-qv__price font-display" x-text="current.priceHtml"></p>
                <p class="product-qv__compare" x-show="current.compareHtml" x-text="current.compareHtml"></p>
                <p class="product-qv__desc" x-text="product?.short_description || ''" x-show="product?.short_description"></p>

                <template x-if="product?.pdp?.productType === 'variable'">
                    <div class="product-qv__options">
                        <template x-for="attr in product.pdp.attributes" :key="attr.name">
                            <fieldset class="product-qv__fieldset">
                                <legend class="product-qv__legend" x-text="attr.name"></legend>
                                <div class="product-qv__option-row">
                                    <template x-for="value in attr.values" :key="value">
                                        <button
                                            type="button"
                                            class="product-qv__option"
                                            :class="{ 'is-selected': selected[attr.name] === value }"
                                            :aria-pressed="selected[attr.name] === value"
                                            @click="selectOption(attr.name, value)"
                                            x-text="value"
                                        ></button>
                                    </template>
                                </div>
                            </fieldset>
                        </template>
                    </div>
                </template>

                <p class="product-qv__status" x-show="product?.pdp?.productType === 'variable' && !matched" x-cloak>
                    Select options to continue.
                </p>
                <p class="product-qv__status" x-show="matched && !matched.purchasable" x-cloak>That option is currently unavailable.</p>

                <div class="product-qv__buy" x-show="canPurchase" x-cloak>
                    <label class="product-qv__qty">
                        <span class="sr-only">Quantity</span>
                        <input type="number" min="1" :max="current.maxQty || 99" x-model.number="quantity" class="product-qv__qty-input">
                    </label>
                    <button type="button" class="product-qv__add" @click="addToCart()" :disabled="adding">
                        <span x-text="adding ? 'Adding…' : 'Add to Cart'"></span>
                    </button>
                </div>

                <div class="product-qv__links">
                    <button
                        type="button"
                        class="product-qv__wish"
                        @click="toggleWishlist()"
                        :aria-pressed="inWishlist"
                        :class="{ 'is-saved': inWishlist }"
                    >
                        <span x-text="inWishlist ? 'Saved' : 'Save to favorites'"></span>
                    </button>
                    <a :href="product?.url" class="product-qv__full">View full details →</a>
                </div>

                <p class="product-qv__flash" x-show="flash" x-text="flash" x-cloak role="status"></p>
            </div>
        </div>

        <div class="product-qv__loading" x-show="loading" x-cloak>
            <p>Loading product…</p>
        </div>

        <div class="product-qv__error" x-show="error" x-cloak>
            <p x-text="error"></p>
            <button type="button" class="product-qv__add" @click="close()">Close</button>
        </div>
    </div>
</div>
