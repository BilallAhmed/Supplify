<div
    x-data="mediaLibraryModal({
        listUrl: @js(route('admin.media.index', ['json' => 1])),
        uploadUrl: @js(route('admin.media.store')),
    })"
    x-cloak
    x-show="open"
    class="media-library"
    @keydown.escape.window="if (open) close()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="media-library-title"
>
    <div class="media-library__backdrop" @click="close()" aria-hidden="true"></div>

    <div
        class="media-library__dialog"
        @click.stop
    >
        <header class="media-library__header">
            <div>
                <h2 id="media-library-title" class="font-display text-xl font-bold text-ink">Media library</h2>
                <p class="text-sm text-ink-muted">Choose an existing image or upload a new one.</p>
            </div>
            <button type="button" class="media-library__close" @click="close()" aria-label="Close media library">
                <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                </svg>
            </button>
        </header>

        <div class="media-library__tabs" role="tablist">
            <button type="button" role="tab" class="media-library__tab" :aria-selected="tab === 'library'" :class="{ 'is-active': tab === 'library' }" @click="tab = 'library'">Media Library</button>
            <button type="button" role="tab" class="media-library__tab" :aria-selected="tab === 'upload'" :class="{ 'is-active': tab === 'upload' }" @click="tab = 'upload'">Upload New</button>
        </div>

        <p x-show="notice" x-text="notice" class="media-library__notice" role="status"></p>

        <div class="media-library__body" x-show="tab === 'library'">
            <div class="media-library__toolbar">
                <input
                    type="search"
                    x-ref="searchInput"
                    x-model="q"
                    @keydown.enter.prevent="search()"
                    placeholder="Search by filename or title…"
                    class="ui-input media-library__search"
                    aria-label="Search media"
                >
                <x-button type="button" variant="outline" size="sm" @click="search()">Search</x-button>
                <x-button type="button" variant="ghost" size="sm" @click="reindex()" data-tooltip="Re-scan site images without copying files">Re-index</x-button>
            </div>

            <div class="media-library__layout">
                <div class="media-library__grid-wrap">
                    <div x-show="loading" class="media-library__loading">Loading…</div>

                    <div x-show="!loading && items.length === 0" class="media-library__empty">
                        <p class="font-medium text-ink">No images yet</p>
                        <p class="mt-1 text-sm text-ink-muted">Upload a file or click Re-index to register images already on the site.</p>
                        <x-button type="button" variant="primary" size="sm" class="mt-3" @click="tab = 'upload'">Upload New</x-button>
                    </div>

                    <ul x-show="!loading && items.length" class="media-library__grid" role="listbox" aria-label="Media items">
                        <template x-for="item in items" :key="item.id">
                            <li>
                                <button
                                    type="button"
                                    class="media-library__tile"
                                    :class="{ 'is-selected': selected?.id === item.id }"
                                    @click="selectItem(item)"
                                    @dblclick="selectItem(item); useSelected()"
                                    role="option"
                                    :aria-selected="selected?.id === item.id"
                                >
                                    <img :src="item.url" :alt="item.alt || item.original_filename" loading="lazy" decoding="async">
                                    <span class="media-library__tile-name" x-text="item.original_filename"></span>
                                </button>
                            </li>
                        </template>
                    </ul>

                    <div class="media-library__pager" x-show="meta.last_page > 1">
                        <x-button type="button" variant="ghost" size="sm" ::disabled="meta.current_page <= 1" @click="fetchPage(meta.current_page - 1)">Previous</x-button>
                        <span class="text-xs text-ink-muted" x-text="`Page ${meta.current_page} of ${meta.last_page} · ${meta.total} images`"></span>
                        <x-button type="button" variant="ghost" size="sm" ::disabled="meta.current_page >= meta.last_page" @click="fetchPage(meta.current_page + 1)">Next</x-button>
                    </div>
                </div>

                <aside class="media-library__details" aria-live="polite">
                    <template x-if="!selected">
                        <p class="text-sm text-ink-muted">Select an image to preview details.</p>
                    </template>
                    <template x-if="selected">
                        <div class="space-y-3">
                            <img :src="selected.url" :alt="selected.alt || selected.original_filename" class="media-library__detail-preview">
                            <dl class="media-library__dl">
                                <div><dt>Filename</dt><dd x-text="selected.original_filename"></dd></div>
                                <div><dt>Type</dt><dd x-text="selected.mime_type"></dd></div>
                                <div><dt>Dimensions</dt><dd x-text="selected.dimensions"></dd></div>
                                <div><dt>Size</dt><dd x-text="selected.human_size"></dd></div>
                                <div x-show="selected.is_locked"><dt>Status</dt><dd>Locked site asset</dd></div>
                            </dl>
                            <div>
                                <x-label for="media-title-draft">Title</x-label>
                                <input id="media-title-draft" type="text" class="ui-input" x-model="titleDraft">
                            </div>
                            <div>
                                <x-label for="media-alt-draft">Alt text</x-label>
                                <input id="media-alt-draft" type="text" class="ui-input" x-model="altDraft">
                            </div>
                            <x-button type="button" variant="outline" size="sm" @click="saveMeta()" ::disabled="savingMeta">
                                <span x-text="savingMeta ? 'Saving…' : 'Save details'"></span>
                            </x-button>
                            <div x-show="usages.length" class="rounded border border-line bg-surface-muted/50 p-2 text-xs text-ink-muted">
                                <p class="font-semibold text-ink">Used in</p>
                                <ul class="mt-1 list-disc pl-4">
                                    <template x-for="u in usages" :key="u">
                                        <li x-text="u"></li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </template>
                </aside>
            </div>
        </div>

        <div class="media-library__body" x-show="tab === 'upload'" x-cloak>
            <div
                class="media-library__drop"
                :class="{ 'is-drag': dragOver }"
                @dragover.prevent="dragOver = true"
                @dragleave.prevent="dragOver = false"
                @drop.prevent="dragOver = false; onFiles($event.dataTransfer.files)"
            >
                <p class="font-medium text-ink">Drag and drop an image here</p>
                <p class="mt-1 text-sm text-ink-muted">JPG, PNG, WebP, or GIF · max 2MB</p>
                <label class="mt-4 inline-flex cursor-pointer">
                    <span class="ui-button-primary rounded-[var(--radius-control)] bg-brand px-4 py-2 text-sm font-medium text-white">Browse files</span>
                    <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp,image/gif" @change="onFiles($event.target.files); $event.target.value = ''">
                </label>
                <div x-show="uploading" class="mt-4 w-full max-w-xs">
                    <div class="h-2 overflow-hidden rounded-full bg-surface-muted">
                        <div class="h-full bg-accent transition-all" :style="`width:${uploadProgress}%`"></div>
                    </div>
                    <p class="mt-1 text-xs text-ink-muted" x-text="`Uploading ${uploadProgress}%`"></p>
                </div>
                <p x-show="uploadError" class="mt-3 text-sm text-danger" x-text="uploadError" role="alert"></p>
            </div>
        </div>

        <footer class="media-library__footer">
            <x-button type="button" variant="ghost" @click="close()">Cancel</x-button>
            <x-button type="button" variant="primary" @click="useSelected()" ::disabled="!selected">Use this image</x-button>
        </footer>
    </div>
</div>
