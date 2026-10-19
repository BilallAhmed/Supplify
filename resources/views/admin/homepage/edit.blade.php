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

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-label for="title-{{ $section->id }}">Title</x-label>
                            <x-input id="title-{{ $section->id }}" name="title" :value="old('title', $section->title)" />
                        </div>
                        <div>
                            <x-label for="subtitle-{{ $section->id }}">Subtitle / eyebrow</x-label>
                            <x-input id="subtitle-{{ $section->id }}" name="subtitle" :value="old('subtitle', $section->subtitle)" />
                        </div>
                    </div>

                    <div>
                        <x-label for="body-{{ $section->id }}">Body</x-label>
                        <x-textarea id="body-{{ $section->id }}" name="body" rows="4">{{ old('body', $section->body) }}</x-textarea>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-label for="cta_label-{{ $section->id }}">CTA label</x-label>
                            <x-input id="cta_label-{{ $section->id }}" name="cta_label" :value="old('cta_label', $section->cta_label)" />
                        </div>
                        <div>
                            <x-label for="cta_url-{{ $section->id }}">CTA URL</x-label>
                            <x-input id="cta_url-{{ $section->id }}" name="cta_url" :value="old('cta_url', $section->cta_url)" placeholder="/shop" />
                        </div>
                    </div>

                    @if (in_array($section->key, ['promo', 'story', 'hero'], true))
                        <div class="space-y-3 rounded-[var(--radius-control)] border border-line/70 bg-surface p-4">
                            <x-media-picker
                                name="image_path"
                                label="Section image"
                                :value="old('image_path', $section->image_path)"
                                help="Select from the media library or upload a new image. Removing clears the association only."
                            />
                        </div>
                    @endif

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-label for="sort_order-{{ $section->id }}">Sort order</x-label>
                            <x-input id="sort_order-{{ $section->id }}" name="sort_order" type="number" min="0" :value="old('sort_order', $section->sort_order)" />
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex cursor-pointer items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="is_enabled"
                                    value="1"
                                    class="mt-1 size-4 rounded border-line text-brand focus:ring-brand/30"
                                    @checked((bool) old('is_enabled', $section->is_enabled))
                                >
                                <span class="text-sm text-ink">Enabled on homepage</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <x-label for="config_json-{{ $section->id }}">Advanced config (JSON)</x-label>
                        <x-textarea id="config_json-{{ $section->id }}" name="config_json" rows="5">{{ old('config_json', json_encode($section->config ?? new \stdClass, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</x-textarea>
                        <p class="ui-help">
                            @if ($section->key === 'story')
                                Journey stages live in <code>beats</code> (label, title, body, icon). CTA should point to About (<code>/pages/about</code>).
                            @elseif ($section->key === 'promo')
                                Optional schedule: <code>starts_at</code> / <code>ends_at</code> (ISO datetime). Banner stays hidden until enabled with real copy or an image — no placeholder sales.
                            @elseif ($section->key === 'trust')
                                Trust strip items live in <code>items</code> (title, body). Optional <code>marquee</code> string list.
                            @else
                                Used for hero slides, featured limits, trust items, and secondary CTAs.
                            @endif
                        </p>
                    </div>

                    <x-button type="submit" variant="primary" size="sm">Save {{ $section->label() }}</x-button>
                </form>
            </details>
        @endforeach
    </div>
@endsection
