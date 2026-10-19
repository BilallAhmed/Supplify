@php
    /** @var \App\Models\Page $page */
    $isEdit = $page->exists;
    $submitLabel = $isEdit ? 'Save changes' : 'Create page';
    $canView = $isEdit && filled($page->slug) && $page->is_published;
    $viewLabel = $page->isAboutPage() ? 'View About' : ($page->isContactPage() ? 'View Contact' : 'View page');
@endphp

<div class="admin-page-editor__sidebar-inner">
    <section class="admin-panel admin-panel--accent">
        <header class="admin-panel__head">
            <h2 class="admin-panel__title">Publish</h2>
            <p class="admin-panel__intro">Save your changes and control visibility.</p>
        </header>
        <div class="admin-panel__body space-y-4">
            <label class="flex cursor-pointer items-start gap-3 rounded-[var(--radius-control)] border border-line bg-surface p-3">
                <input
                    type="checkbox"
                    name="is_published"
                    value="1"
                    class="mt-0.5 size-4 rounded border-line text-brand focus:ring-brand/30"
                    @checked((bool) old('is_published', $page->is_published))
                >
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-ink">Published</span>
                    <span class="mt-0.5 block text-xs text-ink-muted">Visible on the storefront when enabled.</span>
                </span>
            </label>

            <x-button type="submit" variant="primary" class="admin-page-editor__aside-save w-full justify-center">
                {{ $submitLabel }}
            </x-button>

            <div class="flex flex-col gap-2">
                @if ($canView)
                    <x-button
                        href="{{ route('pages.show', $page->slug) }}"
                        variant="outline"
                        class="w-full justify-center"
                        target="_blank"
                        rel="noopener"
                    >
                        {{ $viewLabel }}
                    </x-button>
                @endif
                <x-button href="{{ route('admin.pages.index') }}" variant="ghost" class="w-full justify-center">
                    {{ $isEdit ? 'Back to pages' : 'Cancel' }}
                </x-button>
            </div>
        </div>
    </section>

    <section class="admin-panel">
        <header class="admin-panel__head">
            <h2 class="admin-panel__title">Page settings</h2>
        </header>
        <div class="admin-panel__body space-y-4">
            <div>
                <x-label for="slug">Slug</x-label>
                <x-input id="slug" name="slug" :value="old('slug', $page->slug)" placeholder="auto-from-title" />
                <p class="ui-help">Used in the URL. Leave blank to generate from the title.</p>
            </div>

            @if ($isEdit)
                <dl class="admin-page-meta">
                    <div>
                        <dt>Updated</dt>
                        <dd>{{ optional($page->updated_at)->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Created</dt>
                        <dd>{{ optional($page->created_at)->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}</dd>
                    </div>
                    @if ($page->published_at)
                        <div>
                            <dt>First published</dt>
                            <dd>{{ $page->published_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</dd>
                        </div>
                    @endif
                </dl>
            @endif
        </div>
    </section>

    <details class="admin-panel admin-panel--details" @if ($errors->has('seo_title') || $errors->has('seo_description')) open @endif>
        <summary class="admin-panel__summary">
            <span class="admin-panel__title">SEO</span>
            <span class="admin-panel__summary-hint">Optional search metadata</span>
        </summary>
        <div class="admin-panel__body space-y-4 border-t border-line pt-4">
            <div>
                <x-label for="seo_title">SEO title</x-label>
                <x-input id="seo_title" name="seo_title" :value="old('seo_title', $page->seo_title)" />
            </div>
            <div>
                <x-label for="seo_description">SEO description</x-label>
                <x-textarea id="seo_description" name="seo_description" rows="3">{{ old('seo_description', $page->seo_description) }}</x-textarea>
            </div>
        </div>
    </details>
</div>
