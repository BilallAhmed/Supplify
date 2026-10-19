@extends('layouts.admin')

@section('title', 'Media library')
@section('breadcrumb', 'Media')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Media library</h1>
        <p class="mt-1 text-sm text-ink-muted">Browse, upload, and manage images used across the storefront.</p>
    </div>
@endsection

@section('content')
    <div
        class="rounded-[var(--radius-panel)] border border-line bg-surface-elevated p-5 shadow-soft sm:p-6"
        x-data="mediaLibraryModal({
            listUrl: @js(route('admin.media.index', ['json' => 1])),
            uploadUrl: @js(route('admin.media.store')),
        })"
        x-init="fetchPage(1)"
    >
        <div class="mb-4 flex flex-wrap gap-2">
            <x-button type="button" variant="primary" @click="reindex()">Re-index site images</x-button>
            <x-button type="button" variant="outline" @click="tab = 'upload'">Upload New</x-button>
        </div>
        <div class="media-library__tabs !border-0 !px-0" role="tablist">
            <button type="button" role="tab" class="media-library__tab" :class="{ 'is-active': tab === 'library' }" @click="tab = 'library'">Media Library</button>
            <button type="button" role="tab" class="media-library__tab" :class="{ 'is-active': tab === 'upload' }" @click="tab = 'upload'">Upload New</button>
        </div>

        <p x-show="notice" x-text="notice" class="media-library__notice !mx-0" role="status"></p>

        <div class="mt-4" x-show="tab === 'library'">
            <div class="media-library__toolbar">
                <input type="search" x-model="q" @keydown.enter.prevent="search()" placeholder="Search by filename or title…" class="ui-input media-library__search" aria-label="Search media">
                <x-button type="button" variant="outline" size="sm" @click="search()">Search</x-button>
            </div>

            <div class="media-library__layout">
                <div>
                    <div x-show="loading" class="media-library__loading">Loading…</div>
                    <div x-show="!loading && items.length === 0" class="media-library__empty">
                        <p class="font-medium text-ink">No images indexed yet</p>
                        <p class="mt-1 text-sm text-ink-muted">Click “Re-index site images” above, or upload a new file.</p>
                    </div>
                    <ul x-show="!loading && items.length" class="media-library__grid" role="listbox">
                        <template x-for="item in items" :key="item.id">
                            <li>
                                <button type="button" class="media-library__tile" :class="{ 'is-selected': selected?.id === item.id }" @click="selectItem(item)">
                                    <img :src="item.url" :alt="item.alt || item.original_filename" loading="lazy">
                                    <span class="media-library__tile-name" x-text="item.original_filename"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <div class="media-library__pager" x-show="meta.last_page > 1">
                        <x-button type="button" variant="ghost" size="sm" ::disabled="meta.current_page <= 1" @click="fetchPage(meta.current_page - 1)">Previous</x-button>
                        <span class="text-xs text-ink-muted" x-text="`Page ${meta.current_page} of ${meta.last_page}`"></span>
                        <x-button type="button" variant="ghost" size="sm" ::disabled="meta.current_page >= meta.last_page" @click="fetchPage(meta.current_page + 1)">Next</x-button>
                    </div>
                </div>
                <aside class="media-library__details">
                    <template x-if="!selected">
                        <p class="text-sm text-ink-muted">Select an image to view details.</p>
                    </template>
                    <template x-if="selected">
                        <div class="space-y-3">
                            <img :src="selected.url" class="media-library__detail-preview" :alt="selected.alt || selected.original_filename">
                            <p class="text-sm font-semibold text-ink" x-text="selected.original_filename"></p>
                            <p class="text-xs text-ink-muted" x-text="`${selected.dimensions} · ${selected.human_size}`"></p>
                            <div>
                                <x-label>Alt text</x-label>
                                <input type="text" class="ui-input" x-model="altDraft">
                            </div>
                            <div>
                                <x-label>Title</x-label>
                                <input type="text" class="ui-input" x-model="titleDraft">
                            </div>
                            <x-button type="button" variant="outline" size="sm" @click="saveMeta()">Save details</x-button>
                            <div x-show="usages.length" class="text-xs text-ink-muted">
                                <p class="font-semibold text-ink">Used in</p>
                                <ul class="mt-1 list-disc pl-4">
                                    <template x-for="u in usages" :key="u"><li x-text="u"></li></template>
                                </ul>
                            </div>
                        </div>
                    </template>
                </aside>
            </div>
        </div>

        <div class="mt-4" x-show="tab === 'upload'" x-cloak>
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
                    <span class="rounded-[var(--radius-control)] bg-brand px-4 py-2 text-sm font-medium text-white">Browse files</span>
                    <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp,image/gif" @change="onFiles($event.target.files); $event.target.value = ''">
                </label>
                <p x-show="uploadError" class="mt-3 text-sm text-danger" x-text="uploadError" role="alert"></p>
            </div>
        </div>
    </div>
@endsection
