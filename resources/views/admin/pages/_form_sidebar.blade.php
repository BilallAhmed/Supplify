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
            <x-checkbox
                name="is_published"
                value="1"
                label="Published"
                help="Visible on the storefront when enabled."
                :checked="(bool) old('is_published', $page->is_published)"
            />

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
            <x-input
                id="slug"
                name="slug"
                label="Slug"
                :value="old('slug', $page->slug)"
                placeholder="auto-from-title"
                help="Used in the URL. Leave blank to generate from the title."
            />

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
            <x-input id="seo_title" name="seo_title" label="SEO title" :value="old('seo_title', $page->seo_title)" />
            <x-textarea id="seo_description" name="seo_description" label="SEO description" rows="3">{{ old('seo_description', $page->seo_description) }}</x-textarea>
        </div>
    </details>
</div>
