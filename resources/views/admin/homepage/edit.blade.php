@extends('layouts.admin')

@section('title', 'Homepage content')
@section('breadcrumb', 'Homepage')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Homepage sections</h1>
        <p class="mt-1 text-sm text-ink-muted">Enable, edit, and reorder structured sections. No page builder.</p>
    </div>
    <x-button href="{{ route('home') }}" variant="outline" target="_blank">View storefront</x-button>
@endsection

@section('content')
    <div class="space-y-5">
        @foreach ($sections as $section)
            <details class="rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft open:shadow-lift" @if ($loop->first) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <p class="font-display font-bold text-ink">{{ $section->label() }}</p>
                        <p class="text-xs text-ink-subtle">key: {{ $section->key }} · sort {{ $section->sort_order }}</p>
                    </div>
                    <x-badge :variant="$section->is_enabled ? 'success' : 'neutral'">
                        {{ $section->is_enabled ? 'Enabled' : 'Disabled' }}
                    </x-badge>
                </summary>

                <form
                    method="POST"
                    action="{{ route('admin.homepage.update', $section) }}"
                    enctype="multipart/form-data"
                    class="space-y-4 border-t border-line px-5 py-5"
                >
                    @csrf
                    @method('PUT')

                    @php
                        $configHelp = match ($section->key) {
                            'story' => 'Journey stages live in beats (label, title, body, icon). CTA should point to About (/pages/about).',
                            'promo' => 'Optional schedule: starts_at / ends_at (ISO datetime). Banner stays hidden until enabled with real copy or an image.',
                            'trust' => 'Trust strip items live in items (title, body). Optional marquee string list.',
                            'hero' => 'slides array of lifestyle banners with scene, tone, focus, and copy fields. Prefer images/homepage/hero-lifestyle-*.webp.',
                            default => 'Used for featured limits, education limits, and secondary CTAs.',
                        };
                    @endphp

                    <div class="admin-form-grid">
                        <x-input id="title-{{ $section->id }}" name="title" label="Title" :value="old('title', $section->title)" />
                        <x-input id="subtitle-{{ $section->id }}" name="subtitle" label="Subtitle / eyebrow" :value="old('subtitle', $section->subtitle)" />
                        <x-textarea id="body-{{ $section->id }}" name="body" label="Body" rows="4">{{ old('body', $section->body) }}</x-textarea>
                        <x-input id="cta_label-{{ $section->id }}" name="cta_label" label="CTA label" :value="old('cta_label', $section->cta_label)" />
                        <x-input id="cta_url-{{ $section->id }}" name="cta_url" label="CTA URL" :value="old('cta_url', $section->cta_url)" placeholder="/shop" />

                        @if (in_array($section->key, ['promo', 'story', 'hero'], true))
                            <x-media-picker
                                name="image_path"
                                label="Section image"
                                :value="old('image_path', $section->image_path)"
                                help="Select from the media library or upload a new image. Removing clears the association only."
                            />
                        @endif

                        <x-input id="sort_order-{{ $section->id }}" name="sort_order" label="Sort order" type="number" min="0" :value="old('sort_order', $section->sort_order)" />
                        <x-checkbox
                            name="is_enabled"
                            value="1"
                            label="Enabled on homepage"
                            :checked="(bool) old('is_enabled', $section->is_enabled)"
                        />

                        <x-textarea
                            id="config_json-{{ $section->id }}"
                            name="config_json"
                            label="Advanced config (JSON)"
                            rows="5"
                            :help="$configHelp"
                        >{{ old('config_json', json_encode($section->config ?? new \stdClass, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</x-textarea>
                    </div>

                    <div class="admin-form__actions">
                        <x-button type="submit" variant="primary" size="sm">Save {{ $section->label() }}</x-button>
                    </div>
                </form>
            </details>
        @endforeach
    </div>
@endsection
