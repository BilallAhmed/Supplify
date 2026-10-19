document.addEventListener('alpine:init', () => {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    window.Alpine.data('mediaPickerField', (config = {}) => ({
        name: config.name || '',
        value: config.value || '',
        previewUrl: config.previewUrl || '',
        filename: config.filename || '',
        dimensions: config.dimensions || '',
        label: config.label || 'Image',
        help: config.help || '',
        required: !!config.required,
        openEvent: config.openEvent || 'media-library:open',
        fieldId: config.fieldId || `media-field-${Math.random().toString(36).slice(2, 9)}`,

        init() {
            if (this.value && ! this.previewUrl) {
                this.resolvePreview();
            }
            window.addEventListener('media-library:selected', (e) => {
                if (e.detail?.fieldId !== this.fieldId) return;
                const asset = e.detail.asset;
                if (! asset) return;
                this.value = asset.reference || asset.path || '';
                this.previewUrl = asset.url || '';
                this.filename = asset.original_filename || '';
                this.dimensions = asset.dimensions || '';
            });
        },

        async resolvePreview() {
            try {
                const { data } = await window.axios.get('/admin/media/resolve', {
                    params: { reference: this.value },
                });
                this.previewUrl = data.url || '';
                this.filename = data.filename || this.filename;
                if (data.width && data.height) {
                    this.dimensions = `${data.width}×${data.height}`;
                }
                if (data.asset?.original_filename) {
                    this.filename = data.asset.original_filename;
                    this.dimensions = data.asset.dimensions || this.dimensions;
                }
            } catch {
                // Keep existing preview if resolve fails.
            }
        },

        openLibrary() {
            window.dispatchEvent(new CustomEvent(this.openEvent, {
                detail: {
                    fieldId: this.fieldId,
                    current: this.value,
                    multiple: false,
                },
            }));
        },

        clear() {
            this.value = '';
            this.previewUrl = '';
            this.filename = '';
            this.dimensions = '';
        },
    }));

    window.Alpine.data('mediaLibraryModal', (config = {}) => ({
        open: false,
        tab: 'library',
        q: '',
        items: [],
        meta: { current_page: 1, last_page: 1, total: 0 },
        loading: false,
        uploading: false,
        uploadProgress: 0,
        uploadError: '',
        selected: null,
        usages: [],
        fieldId: null,
        focusBefore: null,
        listUrl: config.listUrl || '/admin/media?json=1',
        uploadUrl: config.uploadUrl || '/admin/media',
        dragOver: false,
        altDraft: '',
        titleDraft: '',
        savingMeta: false,
        notice: '',

        init() {
            window.addEventListener('media-library:open', (e) => {
                this.openModal(e.detail || {});
            });
            this.$watch('open', (value) => {
                document.body.classList.toggle('overflow-hidden', value);
            });
        },

        openModal(detail = {}) {
            this.focusBefore = document.activeElement;
            this.fieldId = detail.fieldId || null;
            this.tab = 'library';
            this.q = '';
            this.selected = null;
            this.usages = [];
            this.uploadError = '';
            this.notice = '';
            this.open = true;
            this.fetchPage(1);
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        },

        close() {
            this.open = false;
            this.$nextTick(() => {
                if (this.focusBefore && typeof this.focusBefore.focus === 'function') {
                    this.focusBefore.focus();
                }
            });
        },

        async fetchPage(page = 1) {
            this.loading = true;
            try {
                const { data } = await window.axios.get(this.listUrl, {
                    params: { q: this.q || undefined, page, per_page: 24 },
                });
                this.items = data.data || [];
                this.meta = data.meta || this.meta;
            } catch {
                this.items = [];
                this.notice = 'Could not load the media library.';
            } finally {
                this.loading = false;
            }
        },

        search() {
            this.fetchPage(1);
        },

        selectItem(item) {
            this.selected = item;
            this.altDraft = item.alt || '';
            this.titleDraft = item.title || '';
            this.usages = [];
            this.loadUsages(item.id);
        },

        async loadUsages(id) {
            try {
                const { data } = await window.axios.get(`/admin/media/${id}`);
                this.usages = data.usages || [];
                if (data.data) {
                    this.selected = data.data;
                }
            } catch {
                this.usages = [];
            }
        },

        async saveMeta() {
            if (! this.selected?.id) return;
            this.savingMeta = true;
            try {
                const { data } = await window.axios.patch(`/admin/media/${this.selected.id}`, {
                    alt: this.altDraft,
                    title: this.titleDraft,
                });
                this.selected = data.data;
                const idx = this.items.findIndex((i) => i.id === this.selected.id);
                if (idx >= 0) this.items[idx] = this.selected;
                this.notice = 'Details saved.';
            } catch {
                this.notice = 'Could not save details.';
            } finally {
                this.savingMeta = false;
            }
        },

        useSelected() {
            if (! this.selected) return;
            window.dispatchEvent(new CustomEvent('media-library:selected', {
                detail: {
                    fieldId: this.fieldId,
                    asset: this.selected,
                },
            }));
            this.close();
        },

        onFiles(files) {
            const file = files?.[0];
            if (! file) return;
            this.upload(file);
        },

        async upload(file) {
            this.uploading = true;
            this.uploadProgress = 0;
            this.uploadError = '';
            const body = new FormData();
            body.append('file', file);

            try {
                const { data } = await window.axios.post(this.uploadUrl, body, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': csrf(),
                    },
                    onUploadProgress: (e) => {
                        if (e.total) {
                            this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                        }
                    },
                });
                this.tab = 'library';
                this.selected = data.data;
                this.altDraft = data.data.alt || '';
                this.titleDraft = data.data.title || '';
                await this.fetchPage(1);
                this.notice = 'Upload complete — click Use this image to apply.';
            } catch (error) {
                this.uploadError = error.response?.data?.message
                    || error.response?.data?.errors?.file?.[0]
                    || 'Upload failed.';
            } finally {
                this.uploading = false;
            }
        },

        async reindex() {
            this.loading = true;
            try {
                const { data } = await window.axios.post('/admin/media/reindex');
                this.notice = `Indexed: ${data.result?.created || 0} new, ${data.result?.updated || 0} updated.`;
                await this.fetchPage(1);
            } catch {
                this.notice = 'Indexing failed.';
            } finally {
                this.loading = false;
            }
        },
    }));
});
