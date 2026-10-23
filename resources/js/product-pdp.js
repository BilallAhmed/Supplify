/**
 * Storefront product detail page — gallery, hover zoom, lightbox, purchase sync.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('productDetailsTabs', (tabs = []) => ({
        tabs, active: tabs[0] || '',
        choose(id, focus = false) {
            if (!this.tabs.includes(id)) return;
            this.active = id;
            if (focus) this.$nextTick(() => document.getElementById(`tab-${id}`)?.focus());
        },
        move(step) {
            const index = (this.tabs.indexOf(this.active) + step + this.tabs.length) % this.tabs.length;
            this.choose(this.tabs[index], true);
        },
    }));
    Alpine.data('productPdp', (payload = {}) => ({
        productType: payload.productType || 'simple',
        attributes: payload.attributes || [],
        variations: payload.variations || [],
        selected: { ...(payload.defaultSelection || {}) },
        gallery: Array.isArray(payload.gallery) ? payload.gallery : [],
        activeIndex: 0,
        userPickedImage: false,
        quantity: 1,
        temporaryPricing: !!payload.temporaryPricing,
        productId: payload.productId || null,
        productName: payload.productName || '',

        // Desktop hover zoom
        zoomEnabled: false,
        zoomActive: false,
        zoomScale: 1.8,
        zoomX: 50,
        zoomY: 50,
        lensStyle: {},
        naturalW: 0,
        naturalH: 0,

        // Lightbox
        lightboxOpen: false,
        lightboxZoom: 1,
        lightboxX: 0,
        lightboxY: 0,
        lightboxDragging: false,
        _lbPointer: null,
        _lbPinch: null,
        _focusBeforeLightbox: null,

        // Mobile sticky bar
        purchaseInView: true,
        modalOpen: false,

        base: {
            priceHtml: payload.priceHtml || '',
            compareHtml: payload.compareHtml || '',
            onSale: !!payload.onSale,
            stockLabel: payload.stockLabel || '',
            stockVariant: payload.stockVariant || 'neutral',
            sku: payload.sku || '',
            imageUrl: payload.imageUrl || '',
            imageAlt: payload.imageAlt || '',
            maxQty: payload.maxQty || 1,
            purchasable: !!payload.purchasable,
        },

        init() {
            const startUrl = payload.imageUrl || this.gallery[0]?.url;
            const idx = this.gallery.findIndex((img) => img.url === startUrl);
            this.activeIndex = idx >= 0 ? idx : 0;

            this.zoomEnabled = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

            this.$watch('lightboxOpen', (open) => {
                document.documentElement.classList.toggle('pdp-lightbox-open', open);
                this.modalOpen = open;
                if (open) {
                    this._focusBeforeLightbox = document.activeElement;
                    this.$nextTick(() => this.$refs.lightboxClose?.focus());
                } else {
                    this.resetLightboxTransform();
                    this._focusBeforeLightbox?.focus?.();
                }
            });

            // Hide sticky bar while any storefront modal is open (quick view, etc.)
            const syncModal = () => {
                this.modalOpen = document.documentElement.classList.contains('qv-open')
                    || this.lightboxOpen;
            };
            const mo = new MutationObserver(syncModal);
            mo.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

            this.$nextTick(() => {
                const buy = this.$refs.purchaseBlock;
                if (buy && 'IntersectionObserver' in window) {
                    const io = new IntersectionObserver(
                        ([entry]) => {
                            this.purchaseInView = !!entry?.isIntersecting;
                        },
                        { rootMargin: '-8% 0px -8% 0px', threshold: 0 }
                    );
                    io.observe(buy);
                }
            });
        },

        get matched() {
            if (this.productType !== 'variable') return null;
            const keys = this.attributes.map((a) => a.name);
            if (keys.some((k) => !this.selected[k])) return null;
            return (
                this.variations.find((v) =>
                    keys.every((k) => String(v.options?.[k] || '') === String(this.selected[k] || '')),
                ) || null
            );
        },

        get displayImage() {
            if (this.userPickedImage && this.gallery[this.activeIndex]) {
                return this.gallery[this.activeIndex];
            }

            const v = this.matched;
            if (v?.image_url) {
                return {
                    url: v.image_url,
                    alt: v.image_alt || this.productName,
                    id: `variation-${v.id}`,
                };
            }

            return this.gallery[this.activeIndex] || {
                url: this.base.imageUrl || '',
                alt: this.productName,
            };
        },

        get current() {
            const v = this.matched;
            if (!v) {
                return {
                    ...this.base,
                    imageUrl: this.displayImage.url,
                    imageAlt: this.displayImage.alt,
                };
            }

            return {
                priceHtml: v.price_html,
                compareHtml: v.compare_html || '',
                onSale: !!v.on_sale,
                stockLabel: v.stock_label,
                stockVariant: v.stock_variant,
                sku: v.sku || this.base.sku,
                imageUrl: this.displayImage.url,
                imageAlt: this.displayImage.alt,
                maxQty: v.max_qty,
                purchasable: !!v.purchasable,
                id: v.id,
            };
        },

        get showStickyBar() {
            return !this.purchaseInView && !this.modalOpen && !this.lightboxOpen;
        },

        isThumbActive(index) {
            if (this.userPickedImage || !this.matched?.image_url) {
                return this.activeIndex === index;
            }
            return this.gallery[index]?.url === this.matched.image_url;
        },

        selectImage(index) {
            if (index < 0 || index >= this.gallery.length) return;
            this.activeIndex = index;
            this.userPickedImage = true;
            this.resetHoverZoom();
            if (this.lightboxOpen) {
                this.resetLightboxTransform();
            }
        },

        isOptionDisabled(attrName, value) {
            const trial = { ...this.selected, [attrName]: value };
            return !this.variations.some((v) => {
                if (!v.purchasable && !v.enabled) return false;
                return Object.entries(trial).every(([k, val]) => {
                    if (!val) return true;
                    return String(v.options?.[k] || '') === String(val);
                });
            });
        },

        selectOption(attrName, value) {
            if (this.selected[attrName] === value) {
                this.selected[attrName] = '';
            } else {
                this.selected[attrName] = value;
            }

            // Variation change may introduce its own image; release manual gallery lock.
            this.userPickedImage = false;
            this.resetHoverZoom();

            const v = this.matched;
            if (v?.image_url) {
                const idx = this.gallery.findIndex((img) => img.url === v.image_url);
                if (idx >= 0) this.activeIndex = idx;
            }

            if (this.quantity > this.current.maxQty) {
                this.quantity = Math.max(1, this.current.maxQty || 1);
            }
        },

        canAdd() {
            if (this.temporaryPricing) return false;
            if (this.productType === 'variable') {
                return !!(this.matched && this.matched.purchasable);
            }
            return !!this.base.purchasable;
        },

        unavailableLabel() {
            if (this.temporaryPricing) return 'Unavailable for purchase';
            if (this.productType === 'variable' && !this.matched) return 'Select options';
            return 'Out of stock';
        },

        focusOptions() {
            this.$refs.purchaseBlock?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const first = this.$refs.purchaseBlock?.querySelector('[data-pdp-option]');
            first?.focus();
        },

        // —— Hover zoom ——
        onMainImageLoad(event) {
            const img = event.target;
            this.naturalW = img.naturalWidth || 0;
            this.naturalH = img.naturalHeight || 0;
            this.updateZoomScale(img);
        },

        updateZoomScale(img) {
            if (!img || !this.naturalW) {
                this.zoomScale = 1.5;
                return;
            }
            const rect = img.getBoundingClientRect();
            const displayW = Math.max(1, rect.width);
            // Useful magnification without inventing detail beyond native pixels.
            const nativeRatio = this.naturalW / displayW;
            if (nativeRatio < 1.15) {
                this.zoomScale = Math.max(1.15, nativeRatio);
            } else {
                this.zoomScale = Math.min(2.6, Math.max(1.35, nativeRatio * 0.92));
            }
        },

        onZoomEnter() {
            if (!this.zoomEnabled) return;
            this.zoomActive = true;
        },

        onZoomLeave() {
            this.resetHoverZoom();
        },

        onZoomMove(event) {
            if (!this.zoomEnabled) return;
            const stage = this.$refs.zoomStage;
            const img = this.$refs.mainImage;
            if (!stage || !img || !this.naturalW) return;

            const stageRect = stage.getBoundingClientRect();
            const imageRect = img.getBoundingClientRect();
            const contain = this.containRect(imageRect.width, imageRect.height, this.naturalW, this.naturalH);
            const imageLeft = imageRect.left - stageRect.left + contain.left;
            const imageTop = imageRect.top - stageRect.top + contain.top;
            const px = event.clientX - stageRect.left;
            const py = event.clientY - stageRect.top;
            const inside = px >= imageLeft && px <= imageLeft + contain.width
                && py >= imageTop && py <= imageTop + contain.height;
            this.zoomActive = inside;
            if (!inside) return;
            const size = Math.min(190, stageRect.width * .42);
            const half = size / 2;
            const left = Math.max(0, Math.min(stageRect.width - size, px - half));
            const top = Math.max(0, Math.min(stageRect.height - size, py - half));
            const scale = Math.min(3.5, Math.max(1, this.naturalW / contain.width));
            this.lensStyle = {
                width: `${size}px`, height: `${size}px`, left: `${left}px`, top: `${top}px`,
                backgroundImage: `url(${JSON.stringify(img.currentSrc || img.src)})`,
                backgroundSize: `${contain.width * scale}px ${contain.height * scale}px`,
                backgroundPosition: `${px - left - (px - imageLeft) * scale}px ${py - top - (py - imageTop) * scale}px`,
            };
        },

        resetHoverZoom() {
            this.zoomActive = false;
            this.zoomX = 50;
            this.zoomY = 50;
        },

        containRect(cw, ch, nw, nh) {
            const scale = Math.min(cw / nw, ch / nh);
            const width = nw * scale;
            const height = nh * scale;
            return {
                left: (cw - width) / 2,
                top: (ch - height) / 2,
                width,
                height,
                scale,
            };
        },

        mainImageStyle() {
            return {};
        },

        // —— Lightbox ——
        openLightbox(index = null) {
            if (typeof index === 'number') {
                this.selectImage(index);
            }
            this.lightboxOpen = true;
            this.resetLightboxTransform();
        },

        closeLightbox() {
            this.lightboxOpen = false;
        },

        lightboxPrev() {
            if (this.gallery.length < 2) return;
            const next = (this.activeIndex - 1 + this.gallery.length) % this.gallery.length;
            this.selectImage(next);
            this.resetLightboxTransform();
        },

        lightboxNext() {
            if (this.gallery.length < 2) return;
            const next = (this.activeIndex + 1) % this.gallery.length;
            this.selectImage(next);
            this.resetLightboxTransform();
        },

        lightboxZoomIn() {
            this.lightboxZoom = Math.min(4, +(this.lightboxZoom + 0.35).toFixed(2));
        },

        lightboxZoomOut() {
            this.lightboxZoom = Math.max(1, +(this.lightboxZoom - 0.35).toFixed(2));
            if (this.lightboxZoom === 1) {
                this.lightboxX = 0;
                this.lightboxY = 0;
            }
        },

        resetLightboxTransform() {
            this.lightboxZoom = 1;
            this.lightboxX = 0;
            this.lightboxY = 0;
        },

        lightboxImageStyle() {
            return {
                transform: `translate(${this.lightboxX}px, ${this.lightboxY}px) scale(${this.lightboxZoom})`,
            };
        },

        onLightboxPointerDown(event) {
            if (this.lightboxZoom <= 1) return;
            this.lightboxDragging = true;
            this._lbPointer = {
                id: event.pointerId,
                x: event.clientX - this.lightboxX,
                y: event.clientY - this.lightboxY,
            };
            event.currentTarget.setPointerCapture?.(event.pointerId);
        },

        onLightboxPointerMove(event) {
            if (this._lbPinch) return;
            if (!this.lightboxDragging || !this._lbPointer) return;
            if (event.pointerId !== this._lbPointer.id) return;
            this.lightboxX = event.clientX - this._lbPointer.x;
            this.lightboxY = event.clientY - this._lbPointer.y;
        },

        onLightboxPointerUp(event) {
            if (this._lbPointer && event.pointerId === this._lbPointer.id) {
                this.lightboxDragging = false;
                this._lbPointer = null;
            }
        },

        onLightboxWheel(event) {
            // Confine zoom to lightbox image; do not scroll the page behind.
            event.preventDefault();
            if (event.deltaY < 0) this.lightboxZoomIn();
            else this.lightboxZoomOut();
        },

        onLightboxTouchStart(event) {
            if (event.touches.length === 2) {
                const [a, b] = event.touches;
                const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
                this._lbPinch = { dist, zoom: this.lightboxZoom };
                this.lightboxDragging = false;
                this._lbPointer = null;
            }
        },

        onLightboxTouchMove(event) {
            if (event.touches.length === 2 && this._lbPinch) {
                event.preventDefault();
                const [a, b] = event.touches;
                const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
                const ratio = dist / Math.max(1, this._lbPinch.dist);
                this.lightboxZoom = Math.min(4, Math.max(1, +(this._lbPinch.zoom * ratio).toFixed(2)));
                if (this.lightboxZoom === 1) {
                    this.lightboxX = 0;
                    this.lightboxY = 0;
                }
            }
        },

        onLightboxTouchEnd(event) {
            if (event.touches.length < 2) {
                this._lbPinch = null;
            }
        },

        trapLightboxFocus(event) {
            if (!this.lightboxOpen || event.key !== 'Tab') return;
            const root = this.$refs.lightboxDialog;
            if (!root) return;
            const focusable = [...root.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            )].filter((el) => !el.hasAttribute('disabled') && el.offsetParent !== null);
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    }));
});
