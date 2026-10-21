/**
 * Shop catalog filter drawer: focus trap, scroll lock, Escape, desktop cleanup.
 * Also validates the price range form before submit.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-shop-price-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const minInput = form.querySelector('[name="min_price"]');
            const maxInput = form.querySelector('[name="max_price"]');
            const errorEl = form.querySelector('#shop-price-error');
            if (! minInput || ! maxInput) {
                return;
            }

            const minRaw = String(minInput.value ?? '').trim();
            const maxRaw = String(maxInput.value ?? '').trim();
            const showError = (message) => {
                event.preventDefault();
                minInput.classList.add('is-invalid');
                maxInput.classList.add('is-invalid');
                minInput.setAttribute('aria-invalid', 'true');
                maxInput.setAttribute('aria-invalid', 'true');
                if (errorEl) {
                    errorEl.hidden = false;
                    errorEl.textContent = message;
                }
            };

            const clearError = () => {
                minInput.classList.remove('is-invalid');
                maxInput.classList.remove('is-invalid');
                minInput.setAttribute('aria-invalid', 'false');
                maxInput.setAttribute('aria-invalid', 'false');
                if (errorEl && ! errorEl.dataset.serverError) {
                    errorEl.hidden = true;
                    errorEl.textContent = '';
                }
            };

            clearError();

            if (minRaw === '' && maxRaw === '') {
                return;
            }

            const minVal = minRaw === '' ? null : Number(minRaw);
            const maxVal = maxRaw === '' ? null : Number(maxRaw);

            if ((minRaw !== '' && ! Number.isFinite(minVal)) || (maxRaw !== '' && ! Number.isFinite(maxVal))) {
                showError('Enter valid non-negative prices in PKR.');
                return;
            }

            if ((minVal !== null && minVal < 0) || (maxVal !== null && maxVal < 0)) {
                showError('Price values cannot be negative.');
                return;
            }

            if (minVal !== null && maxVal !== null && minVal > maxVal) {
                showError('Minimum price cannot be greater than maximum price.');
            }
        });
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.data('shopCatalogFilters', () => ({
        open: false,
        isDesktop: false,
        previouslyFocused: null,
        mediaQuery: null,

        init() {
            this.mediaQuery = window.matchMedia('(min-width: 1024px)');
            this.isDesktop = this.mediaQuery.matches;
            this.onViewportChange = () => {
                this.isDesktop = this.mediaQuery.matches;
                if (this.isDesktop && this.open) {
                    this.closeFilters({ restoreFocus: false });
                }
                this.syncInert();
            };
            this.mediaQuery.addEventListener('change', this.onViewportChange);
            this.$watch('open', () => this.syncInert());
            this.syncInert();
        },

        syncInert() {
            const drawer = this.$refs.drawer;
            if (! drawer) {
                return;
            }
            const shouldInert = ! this.isDesktop && ! this.open;
            if (shouldInert) {
                drawer.setAttribute('inert', '');
            } else {
                drawer.removeAttribute('inert');
            }
        },

        destroy() {
            this.mediaQuery?.removeEventListener('change', this.onViewportChange);
            document.documentElement.classList.remove('shop-filters-open');
            document.body.classList.remove('shop-filters-open');
        },

        openFilters() {
            if (this.isDesktop) {
                return;
            }
            this.previouslyFocused = document.activeElement;
            this.open = true;
            document.documentElement.classList.add('shop-filters-open');
            document.body.classList.add('shop-filters-open');
            this.$nextTick(() => this.$refs.closeBtn?.focus());
        },

        closeFilters({ restoreFocus = true } = {}) {
            if (! this.open) {
                return;
            }
            this.open = false;
            document.documentElement.classList.remove('shop-filters-open');
            document.body.classList.remove('shop-filters-open');
            if (restoreFocus && this.previouslyFocused && typeof this.previouslyFocused.focus === 'function') {
                this.previouslyFocused.focus();
            }
            this.previouslyFocused = null;
        },

        trapFocus(event) {
            if (! this.open || this.isDesktop || event.key !== 'Tab') {
                return;
            }
            const root = this.$refs.drawer;
            if (! root) {
                return;
            }
            const focusable = [...root.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            )].filter((el) => ! el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true');
            if (! focusable.length) {
                return;
            }
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
