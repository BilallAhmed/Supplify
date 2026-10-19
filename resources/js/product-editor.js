document.addEventListener('alpine:init', () => {
    Alpine.data('productEditor', (payload = {}) => ({
        tab: 'general',
        productType: payload.productType || 'simple',
        attributes: Array.isArray(payload.attributes) ? payload.attributes : [],
        variations: Array.isArray(payload.variations) ? payload.variations : [],
        gallery: Array.isArray(payload.gallery) ? payload.gallery : [],
        featuredUrl: payload.featuredUrl || null,
        removeFeatured: false,
        removeGalleryIds: [],
        maxVariations: payload.maxVariations || 50,
        dragIndex: null,
        previewNotice: '',

        tabs: [
            { id: 'general', label: 'General' },
            { id: 'pricing', label: 'Pricing & inventory' },
            { id: 'shipping', label: 'Shipping' },
            { id: 'variations', label: 'Attributes & variations' },
            { id: 'details', label: 'Specs & benefits' },
            { id: 'media', label: 'Media' },
            { id: 'seo', label: 'SEO' },
        ],

        init() {
            if (this.attributes.length === 0) {
                this.attributes = [{ name: '', values: '', used_for_variations: true }];
            }
        },

        isVariable() {
            return this.productType === 'variable';
        },

        addAttribute() {
            this.attributes.push({ name: '', values: '', used_for_variations: true });
        },

        removeAttribute(index) {
            this.attributes.splice(index, 1);
            if (this.attributes.length === 0) {
                this.addAttribute();
            }
        },

        parseValues(raw) {
            return String(raw || '')
                .split(/[|,]/)
                .map((v) => v.trim())
                .filter(Boolean);
        },

        generateVariations() {
            const sets = this.attributes
                .filter((a) => a.used_for_variations !== false)
                .map((a) => ({
                    name: String(a.name || '').trim(),
                    values: this.parseValues(a.values),
                }))
                .filter((a) => a.name && a.values.length);

            if (!sets.length) {
                this.previewNotice = 'Add attribute names and values first.';
                return;
            }

            let combos = [{}];
            for (const set of sets) {
                const next = [];
                for (const combo of combos) {
                    for (const value of set.values) {
                        next.push({ ...combo, [set.name]: value });
                    }
                }
                combos = next;
                if (combos.length > this.maxVariations) {
                    this.previewNotice = `Too many combinations (max ${this.maxVariations}). Reduce attribute values.`;
                    return;
                }
            }

            const existing = new Map(
                this.variations.map((v) => [this.optionKey(v.options || {}), v]),
            );

            const generated = combos.map((options) => {
                const key = this.optionKey(options);
                if (existing.has(key)) {
                    return existing.get(key);
                }
                return {
                    id: null,
                    sku: '',
                    price: '',
                    sale_price: '',
                    stock_quantity: 0,
                    manage_stock: true,
                    is_enabled: true,
                    is_default: false,
                    weight: '',
                    length: '',
                    width: '',
                    height: '',
                    options,
                    open: false,
                };
            });

            this.variations = generated;
            this.previewNotice = `Ready: ${generated.length} variation${generated.length === 1 ? '' : 's'}. Review prices and stock, then save.`;
        },

        optionKey(options) {
            return Object.keys(options || {})
                .sort()
                .map((k) => `${k}=${options[k]}`)
                .join('|');
        },

        variationSummary(variation) {
            const opts = variation.options || {};
            const label = Object.entries(opts)
                .map(([k, v]) => `${k}: ${v}`)
                .join(' / ');
            const price = variation.price ? `$${variation.price}` : 'No price';
            const stock = variation.manage_stock === false ? 'Stock not managed' : `Qty ${variation.stock_quantity ?? 0}`;
            return `${label || 'Variation'} · ${price} · ${stock}`;
        },

        addManualVariation() {
            if (this.variations.length >= this.maxVariations) {
                this.previewNotice = `Maximum of ${this.maxVariations} variations reached.`;
                return;
            }
            const options = {};
            this.attributes
                .filter((a) => a.name && a.used_for_variations !== false)
                .forEach((a) => {
                    const values = this.parseValues(a.values);
                    if (values[0]) options[a.name] = values[0];
                });
            this.variations.push({
                id: null,
                sku: '',
                price: '',
                sale_price: '',
                stock_quantity: 0,
                manage_stock: true,
                is_enabled: true,
                is_default: false,
                weight: '',
                length: '',
                width: '',
                height: '',
                options,
                open: true,
            });
        },

        removeVariation(index) {
            this.variations.splice(index, 1);
        },

        setDefault(index) {
            this.variations.forEach((v, i) => {
                v.is_default = i === index;
            });
        },

        onFeaturedChange(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.featuredUrl = URL.createObjectURL(file);
            this.removeFeatured = false;
        },

        clearFeatured() {
            this.featuredUrl = null;
            this.removeFeatured = true;
            const input = this.$refs.featuredInput;
            if (input) input.value = '';
        },

        onGalleryChange(event) {
            // New files are uploaded on save; preview locally.
            const files = Array.from(event.target.files || []);
            files.forEach((file) => {
                this.gallery.push({
                    id: null,
                    url: URL.createObjectURL(file),
                    alt: '',
                    sort_order: this.gallery.length,
                    pending: true,
                });
            });
        },

        markGalleryRemove(id, index) {
            if (id) {
                this.removeGalleryIds.push(id);
            }
            this.gallery.splice(index, 1);
        },

        moveGallery(index, direction) {
            const target = index + direction;
            if (target < 0 || target >= this.gallery.length) return;
            const copy = [...this.gallery];
            const [item] = copy.splice(index, 1);
            copy.splice(target, 0, item);
            this.gallery = copy;
        },

        onDragStart(index) {
            this.dragIndex = index;
        },

        onDrop(index) {
            if (this.dragIndex === null || this.dragIndex === index) return;
            const copy = [...this.gallery];
            const [item] = copy.splice(this.dragIndex, 1);
            copy.splice(index, 0, item);
            this.gallery = copy;
            this.dragIndex = null;
        },
    }));
});
