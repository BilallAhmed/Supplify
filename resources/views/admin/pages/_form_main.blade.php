@php
    /** @var \App\Models\Page $page */
    $aboutHasErrors = $errors->has('about') || collect($errors->keys())->contains(fn ($k) => str_starts_with((string) $k, 'about.'));
    $contactHasErrors = $errors->has('contact') || collect($errors->keys())->contains(fn ($k) => str_starts_with((string) $k, 'contact.'));
@endphp

<div class="admin-page-editor__stack">
    <section class="admin-panel">
        <header class="admin-panel__head">
            <h2 class="admin-panel__title">Content</h2>
            <p class="admin-panel__intro">Page title and primary body copy.</p>
        </header>
        <div class="admin-panel__body space-y-4">
            <div>
                <x-label for="title">Title</x-label>
                <x-input id="title" name="title" :value="old('title', $page->title)" required />
            </div>

            <div>
                <x-label for="body">Body</x-label>
                <x-textarea id="body" name="body" rows="10">{{ old('body', $page->body) }}</x-textarea>
                <p class="ui-help">
                    @if ($page->isAboutPage())
                        Legacy plain-text body is preserved and used as a fallback for the Purpose section when About fields are empty. The dedicated About template is driven by the sections below.
                    @elseif ($page->isContactPage())
                        The dedicated Contact template is driven by the sections below. The body field is not shown on the storefront Contact page.
                    @else
                        Plain text is fine. Line breaks are preserved on the storefront.
                    @endif
                </p>
            </div>
        </div>
    </section>

    @if ($page->isAboutPage() && isset($about) && is_array($about))
        <div @class(['admin-page-editor__sections', 'has-errors' => $aboutHasErrors])>
            @if ($aboutHasErrors)
                <p class="admin-page-editor__error-banner" role="alert">
                    Some About section fields need attention. Open the highlighted panels below.
                </p>
            @endif
            @include('admin.pages._about_sections', ['about' => $about])
        </div>
    @endif

    @if ($page->isContactPage() && isset($contact) && is_array($contact))
        <div @class(['admin-page-editor__sections', 'has-errors' => $contactHasErrors])>
            @if ($contactHasErrors)
                <p class="admin-page-editor__error-banner" role="alert">
                    Some Contact section fields need attention. Open the highlighted panels below.
                </p>
            @endif
            @include('admin.pages._contact_sections', ['contact' => $contact])
        </div>
    @endif
</div>
