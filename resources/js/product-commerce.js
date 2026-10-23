/**
 * Homepage / catalog product actions: wishlist, add to cart, quick view modal.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('quickViewModal', () => ({
        open: false,
        loading: false,
        adding: false,
        error: '',
        flash: '',
        product: null,
        selected: {},
        quantity: 1,
        galleryIndex: 0,
        inWishlist: false,
        triggerEl: null,

        get matched() {
            const pdp = this.product?.pdp;
            if (! pdp || pdp.productType !== 'variable') return null;
            const keys = (pdp.attributes || []).map((a) => a.name);
            if (keys.some((k) => ! this.selected[k])) return null;
            return (
                (pdp.variations || []).find((v) =>
                    keys.every((k) => String(v.options?.[k] || '') === String(this.selected[k] || '')),
                ) || null
            );
        },

        get current() {
            const base = {
                priceHtml: this.product?.price_html || this.product?.pdp?.priceHtml || '',
                compareHtml: this.product?.pdp?.compareHtml || '',
                maxQty: this.product?.pdp?.maxQty || 99,
                purchasable: !! this.product?.pdp?.purchasable,
                id: null,
            };
            const v = this.matched;
            if (! v) return base;
            return {
                priceHtml: v.price_html,
                compareHtml: v.compare_html || '',
                maxQty: v.max_qty || 99,
                purchasable: !! v.purchasable,
                id: v.id,
            };
        },

        get activeImage() {
            const gallery = this.product?.gallery || [];
            return gallery[this.galleryIndex] || gallery[0] || null;
        },

        get canPurchase() {
            if (! this.product || this.product.temporary_pricing) return false;
            if (this.product.pdp?.productType === 'variable') {
                return !!(this.matched && this.matched.purchasable);
            }
            return !! this.product.pdp?.purchasable;
        },

        init() {
            window.addEventListener('open-quick-view', (event) => {
                const slug = event.detail?.slug;
                if (! slug) return;
                this.triggerEl = event.detail?.trigger || document.activeElement;
                this.openProduct(slug);
            });

            this.$watch('open', (value) => {
                document.body.classList.toggle('overflow-hidden', value);
                document.documentElement.classList.toggle('qv-open', value);
                if (value) {
                    this.$nextTick(() => this.$refs.closeBtn?.focus());
                } else if (this.triggerEl && typeof this.triggerEl.focus === 'function') {
                    this.triggerEl.focus();
                    this.triggerEl = null;
                }
            });
        },

        async openProduct(slug) {
            this.open = true;
            this.loading = true;
            this.error = '';
            this.flash = '';
            this.product = null;
            this.galleryIndex = 0;
            this.quantity = 1;
            this.selected = {};

            try {
                const { data } = await window.axios.get(`/products/${encodeURIComponent(slug)}/quick-view`);
                this.product = data;
                this.inWishlist = !! data.in_wishlist;
                this.selected = { ...(data.pdp?.defaultSelection || {}) };
                this.loading = false;
                this.$nextTick(() => this.$refs.closeBtn?.focus());
            } catch (err) {
                this.loading = false;
                this.error = err?.response?.data?.message || 'Unable to load this product.';
            }
        },

        close() {
            this.open = false;
            this.loading = false;
            this.adding = false;
            this.error = '';
            this.flash = '';
            this.product = null;
        },

        selectOption(name, value) {
            if (this.selected[name] === value) {
                this.selected[name] = '';
            } else {
                this.selected[name] = value;
            }
            if (this.quantity > (this.current.maxQty || 99)) {
                this.quantity = Math.max(1, this.current.maxQty || 1);
            }
        },

        async addToCart() {
            if (! this.canPurchase || ! this.product || this.adding) return;
            this.adding = true;
            this.flash = '';

            try {
                const payload = {
                    product_id: this.product.id,
                    quantity: Math.max(1, Number(this.quantity) || 1),
                };
                if (this.matched?.id) {
                    payload.variation_id = this.matched.id;
                }

                const { data } = await window.axios.post(this.product.cart_url || '/cart/items', payload);
                this.flash = data.message || 'Added to cart.';
                if (typeof data.cart_count === 'number') {
                    window.dispatchEvent(new CustomEvent('cart-count-updated', { detail: { count: data.cart_count } }));
                }
            } catch (err) {
                const errors = err?.response?.data?.errors;
                const first = errors ? Object.values(errors).flat()[0] : null;
                this.flash = first || err?.response?.data?.message || 'Could not add to cart.';
            } finally {
                this.adding = false;
            }
        },

        async toggleWishlist() {
            if (! this.product) return;
            try {
                const { data } = await window.axios.post(`/wishlist/${this.product.id}/toggle`);
                this.inWishlist = !! data.saved;
                window.dispatchEvent(new CustomEvent('wishlist-updated', { detail: { ids: data.ids || [] } }));
                this.flash = data.message || '';
            } catch {
                this.flash = 'Could not update favorites.';
            }
        },

        trapFocus(event) {
            if (! this.open || ! this.$refs.dialog) return;
            const focusable = [
                ...this.$refs.dialog.querySelectorAll(
                    'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])',
                ),
            ].filter((el) => ! el.hasAttribute('disabled') && el.offsetParent !== null);

            if (focusable.length === 0) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    }));
});

function csrfToken() {
    return document.head.querySelector('meta[name="csrf-token"]')?.content || '';
}

function syncWishlistButtons(ids) {
    const set = new Set((ids || []).map((id) => Number(id)));
    document.querySelectorAll('[data-product-action="wishlist"]').forEach((btn) => {
        const id = Number(btn.getAttribute('data-product-id'));
        const saved = set.has(id);
        const name = btn.getAttribute('data-product-name') || 'product';
        btn.classList.toggle('is-saved', saved);
        btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
        btn.setAttribute(
            'aria-label',
            saved ? `Remove ${name} from favorites` : `Save ${name} to favorites`,
        );
        btn.dataset.tooltip = saved ? 'Saved to favorites' : 'Save to favorites';
    });
}

async function toggleWishlistButton(btn) {
    const id = btn.getAttribute('data-product-id');
    if (! id) return;
    btn.disabled = true;
    try {
        const { data } = await window.axios.post(`/wishlist/${id}/toggle`);
        window.dispatchEvent(new CustomEvent('wishlist-updated', { detail: { ids: data.ids || [] } }));
    } catch {
        // no-op; button state restored via sync
    } finally {
        btn.disabled = false;
    }
}

async function addSimpleToCart(btn) {
    const id = btn.getAttribute('data-product-id');
    if (! id) return;
    const label = btn.querySelector('[data-cart-label]') || btn;
    const original = label.textContent;
    btn.disabled = true;
    label.textContent = 'Adding…';
    try {
        const { data } = await window.axios.post('/cart/items', {
            product_id: Number(id),
            quantity: 1,
        });
        label.textContent = 'Added';
        if (typeof data.cart_count === 'number') {
            window.dispatchEvent(new CustomEvent('cart-count-updated', { detail: { count: data.cart_count } }));
        }
        setTimeout(() => {
            label.textContent = original;
            btn.disabled = false;
        }, 1200);
    } catch (err) {
        const errors = err?.response?.data?.errors;
        const first = errors ? Object.values(errors).flat()[0] : null;
        label.textContent = first ? 'Unavailable' : 'Try again';
        setTimeout(() => {
            label.textContent = original;
            btn.disabled = false;
        }, 1600);
    }
}

function updateCartBadge(count) {
    const link = document.querySelector('.site-header__icon-btn[href*="cart"]');
    if (! link) return;
    let badge = link.querySelector('.site-header__badge');
    if (count > 0) {
        if (! badge) {
            badge = document.createElement('span');
            badge.className = 'site-header__badge';
            link.appendChild(badge);
        }
        badge.textContent = count > 99 ? '99+' : String(count);
        link.setAttribute('aria-label', `Cart (${count} items)`);
    } else if (badge) {
        badge.remove();
        link.setAttribute('aria-label', 'Cart');
    }
}

function initProductCommerce() {
    const initial = Array.isArray(window.__wishlistIds) ? window.__wishlistIds : [];
    syncWishlistButtons(initial);

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-product-action]');
        if (! btn) return;

        const action = btn.getAttribute('data-product-action');
        if (action === 'quick-view') {
            event.preventDefault();
            event.stopPropagation();
            const slug = btn.getAttribute('data-product-slug');
            window.dispatchEvent(
                new CustomEvent('open-quick-view', {
                    detail: { slug, trigger: btn },
                }),
            );
            return;
        }

        if (action === 'wishlist') {
            event.preventDefault();
            event.stopPropagation();
            toggleWishlistButton(btn);
            return;
        }

        if (action === 'add-to-cart') {
            event.preventDefault();
            event.stopPropagation();
            addSimpleToCart(btn);
        }
    });

    window.addEventListener('wishlist-updated', (event) => {
        syncWishlistButtons(event.detail?.ids || []);
    });

    window.addEventListener('cart-count-updated', (event) => {
        updateCartBadge(Number(event.detail?.count || 0));
    });

    // Ignore unused helper in tree-shaking contexts
    void csrfToken;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProductCommerce);
} else {
    initProductCommerce();
}
