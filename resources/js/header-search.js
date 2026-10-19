document.addEventListener('alpine:init', () => {
    window.Alpine.data('siteHeader', (searchUrl) => ({
        mobileOpen: false,
        scrolled: false,
        searchOpen: false,
        query: '',
        results: [],
        loading: false,
        searched: false,
        searchUrl,
        _searchTimer: null,
        _abort: null,

        init() {
            this.onScroll();
            // Lenis drives scrolled state on the homepage; keep a throttled
            // native fallback for other pages / reduced-motion.
            let scrollRaf = 0;
            window.addEventListener(
                'scroll',
                () => {
                    if (scrollRaf) return;
                    scrollRaf = requestAnimationFrame(() => {
                        scrollRaf = 0;
                        this.onScroll();
                    });
                },
                { passive: true }
            );
        },

        onScroll() {
            const y = window.scrollY || window.pageYOffset || 0;
            // Hysteresis avoids flicker near the threshold (≈16–24px)
            if (this.scrolled) {
                this.scrolled = y > 12;
            } else {
                this.scrolled = y > 20;
            }
        },

        openSearch() {
            this.searchOpen = true;
            this.mobileOpen = false;
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.$refs.searchInput?.focus();
                });
            });
        },

        closeSearch() {
            this.searchOpen = false;
            this.query = '';
            this.results = [];
            this.loading = false;
            this.searched = false;
            this.clearPendingSearch();
        },

        clearPendingSearch() {
            if (this._searchTimer) {
                clearTimeout(this._searchTimer);
                this._searchTimer = null;
            }
            if (this._abort) {
                this._abort.abort();
                this._abort = null;
            }
        },

        onQueryInput() {
            const q = this.query.trim();

            if (this._searchTimer) {
                clearTimeout(this._searchTimer);
            }

            if (q.length < 2) {
                this.results = [];
                this.searched = false;
                this.loading = false;
                if (this._abort) {
                    this._abort.abort();
                    this._abort = null;
                }
                return;
            }

            this.loading = true;
            this._searchTimer = setTimeout(() => this.runSearch(q), 280);
        },

        async runSearch(q) {
            if (this._abort) {
                this._abort.abort();
            }

            const controller = new AbortController();
            this._abort = controller;

            try {
                const { data } = await window.axios.get(this.searchUrl, {
                    params: { q },
                    signal: controller.signal,
                });

                if (this.query.trim() !== q) {
                    return;
                }

                this.results = Array.isArray(data.results) ? data.results : [];
                this.searched = true;
            } catch (error) {
                if (error?.code === 'ERR_CANCELED' || error?.name === 'CanceledError' || error?.name === 'AbortError') {
                    return;
                }
                this.results = [];
                this.searched = true;
            } finally {
                if (this._abort === controller) {
                    this._abort = null;
                    this.loading = false;
                }
            }
        },

        get showPanel() {
            return this.searchOpen && (this.loading || this.searched);
        },

        get productResults() {
            return this.results.filter((item) => item.type === 'product');
        },

        get articleResults() {
            return this.results.filter((item) => item.type === 'article' || item.type === 'post');
        },

        get pageResults() {
            return this.results.filter((item) => item.type === 'page');
        },
    }));
});
