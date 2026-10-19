document.addEventListener('alpine:init', () => {
    window.Alpine.data('productCategoryPicker', (config = {}) => ({
        items: Array.isArray(config.items) ? config.items : [],
        selected: Array.isArray(config.selected) ? config.selected.map(Number) : [],
        quickUrl: config.quickUrl || '',
        name: '',
        busy: false,
        error: '',

        async create() {
            const name = this.name.trim();
            if (! name || this.busy || ! this.quickUrl) {
                return;
            }

            this.busy = true;
            this.error = '';

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const { data } = await window.axios.post(
                    this.quickUrl,
                    { name },
                    { headers: token ? { 'X-CSRF-TOKEN': token } : {} },
                );

                const cat = data.category;
                if (! this.items.some((item) => Number(item.id) === Number(cat.id))) {
                    this.items.push(cat);
                    this.items.sort((a, b) => a.name.localeCompare(b.name));
                }

                if (! this.selected.includes(Number(cat.id))) {
                    this.selected.push(Number(cat.id));
                }

                this.name = '';
            } catch (error) {
                this.error = error.response?.data?.message
                    || error.response?.data?.errors?.name?.[0]
                    || 'Could not create category.';
            } finally {
                this.busy = false;
            }
        },
    }));
});
