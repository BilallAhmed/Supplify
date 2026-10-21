@extends('layouts.admin')

@section('title', 'Tracking & Scripts')
@section('breadcrumb', 'Settings / Tracking & Scripts')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Tracking & Scripts</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Manage Google Tag Manager, Analytics, Meta Pixel, and trusted custom snippets for the public storefront.
        </p>
    </div>
@endsection

@section('content')
    @include('admin.settings._nav')

    @if ($forceDisabled)
        <x-alert variant="warning" class="mb-5">
            Tracking is force-disabled via <code>TRACKING_FORCE_DISABLE</code>. Enabled integrations will not load until that env flag is cleared.
        </x-alert>
    @endif

    <div class="admin-callout mb-5">
        <p class="admin-callout__title">Before you enable integrations</p>
        <ul class="admin-callout__list">
            <li>Snippets run on the <strong>public storefront only</strong> — never on admin pages or in this editor.</li>
            <li>If you already fire GA4 or Meta tags <strong>inside Google Tag Manager</strong>, leave the separate GA4 / Meta toggles off here to avoid duplicate loading.</li>
            <li>Custom snippets execute as raw HTML/JS for every visitor who meets consent and environment rules. Only paste code from trusted providers.</li>
            <li>Current app environment: <code>{{ $currentEnvironment }}</code>. Scripts load only in environments checked below.</li>
        </ul>
    </div>

    <form
        method="POST"
        action="{{ route('admin.settings.tracking.update') }}"
        class="admin-form"
        x-data="trackingSnippetsEditor(@js(old('custom_snippets', $config['custom_snippets'])))"
    >
        @csrf
        @method('PUT')

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Environment &amp; consent</h2>
                <p class="admin-form__section-desc">
                    Keep staging and local previews from sending production analytics unless you intentionally opt in.
                </p>
            </div>

            <fieldset class="admin-form-grid">
                <legend class="sr-only">Environments that may load tracking</legend>
                @foreach ($allowedEnvironments as $env)
                    <x-checkbox
                        name="environments[]"
                        :value="$env"
                        :label="ucfirst($env)"
                        :checked="in_array($env, old('environments', $config['environments']), true)"
                        :help="$env === 'production' ? 'Recommended for live traffic.' : 'Only enable for deliberate testing.'"
                    />
                @endforeach
            </fieldset>
            @error('environments')
                <p class="ui-error mt-2" role="alert">{{ $message }}</p>
            @enderror
            @error('environments.*')
                <p class="ui-error mt-2" role="alert">{{ $message }}</p>
            @enderror

            <div class="mt-5">
                <x-checkbox
                    name="consent_required"
                    value="1"
                    label="Require cookie consent before non-essential tracking"
                    :checked="(bool) old('consent_required', $config['consent_required'])"
                    help="When enabled, analytics and marketing scripts wait until the visitor accepts the matching category. Essential site cookies are unaffected."
                />
            </div>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Google Tag Manager</h2>
                <p class="admin-form__section-desc">
                    Container script in <code>&lt;head&gt;</code>; noscript iframe immediately after <code>&lt;body&gt;</code>.
                    Prefer managing GA4 and Meta inside GTM when this is enabled.
                </p>
            </div>
            <div class="admin-form-grid">
                <x-checkbox
                    name="gtm_enabled"
                    value="1"
                    label="Enable Google Tag Manager"
                    :checked="(bool) old('gtm_enabled', $config['gtm']['enabled'])"
                />
                <x-input
                    label="Container ID"
                    name="gtm_container_id"
                    :value="old('gtm_container_id', $config['gtm']['container_id'])"
                    placeholder="GTM-XXXXXXX"
                    maxlength="32"
                    help="Format: GTM- followed by letters/numbers."
                    class="admin-form-grid__span"
                />
            </div>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Google Analytics 4</h2>
                <p class="admin-form__section-desc">
                    Loads the official gtag.js snippet in <code>&lt;head&gt;</code>.
                    Do not enable this if the same measurement ID is already configured in GTM.
                </p>
            </div>
            <div class="admin-form-grid">
                <x-checkbox
                    name="ga4_enabled"
                    value="1"
                    label="Enable Google Analytics 4"
                    :checked="(bool) old('ga4_enabled', $config['ga4']['enabled'])"
                    help="Skipped automatically on the storefront while GTM is actively loading, to prevent duplicates."
                />
                <x-input
                    label="Measurement ID"
                    name="ga4_measurement_id"
                    :value="old('ga4_measurement_id', $config['ga4']['measurement_id'])"
                    placeholder="G-XXXXXXXXXX"
                    maxlength="32"
                    help="Format: G- followed by letters/numbers."
                    class="admin-form-grid__span"
                />
            </div>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Meta Pixel</h2>
                <p class="admin-form__section-desc">
                    Pixel script in <code>&lt;head&gt;</code>; noscript image immediately after <code>&lt;body&gt;</code>.
                    Do not enable if the same Pixel is already managed in GTM.
                </p>
            </div>
            <div class="admin-form-grid">
                <x-checkbox
                    name="meta_enabled"
                    value="1"
                    label="Enable Meta Pixel"
                    :checked="(bool) old('meta_enabled', $config['meta']['enabled'])"
                    help="Skipped automatically on the storefront while GTM is actively loading, to prevent duplicates."
                />
                <x-input
                    label="Pixel ID"
                    name="meta_pixel_id"
                    :value="old('meta_pixel_id', $config['meta']['pixel_id'])"
                    placeholder="123456789012345"
                    maxlength="20"
                    help="Digits only (5–20 characters)."
                    class="admin-form-grid__span"
                />
            </div>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="admin-form__section-title">Custom snippets</h2>
                        <p class="admin-form__section-desc">
                            Named HTML/JS entries with an explicit placement. Code is saved and rendered as-is on the storefront — it is not executed or previewed in this admin screen.
                        </p>
                    </div>
                    <x-button type="button" variant="outline" size="sm" @click="addSnippet()">Add snippet</x-button>
                </div>
            </div>

            <div class="admin-callout admin-callout--muted mb-4">
                <p class="admin-callout__title">Placements</p>
                <ul class="admin-callout__list">
                    <li><strong>Head</strong> — before <code>&lt;/head&gt;</code></li>
                    <li><strong>Body start</strong> — immediately after <code>&lt;body&gt;</code></li>
                    <li><strong>Body end</strong> — before <code>&lt;/body&gt;</code></li>
                </ul>
            </div>

            <template x-if="snippets.length === 0">
                <p class="admin-dash__empty">No custom snippets yet. Add one for trusted third-party tools that are not covered above.</p>
            </template>

            <div class="space-y-4">
                <template x-for="(snippet, index) in snippets" :key="snippet.id || index">
                    <div class="admin-snippet-card">
                        <input type="hidden" :name="`custom_snippets[${index}][id]`" :value="snippet.id">
                        <div class="admin-form-grid">
                            <div class="ui-field">
                                <x-label value="Name" />
                                <input
                                    type="text"
                                    class="ui-input"
                                    :name="`custom_snippets[${index}][name]`"
                                    x-model="snippet.name"
                                    maxlength="120"
                                    required
                                    placeholder="e.g. Hotjar"
                                >
                            </div>
                            <div class="ui-field">
                                <x-label value="Placement" />
                                <select class="ui-input" :name="`custom_snippets[${index}][placement]`" x-model="snippet.placement" required>
                                    <option value="head">Head (before &lt;/head&gt;)</option>
                                    <option value="body_start">Body start (after &lt;body&gt;)</option>
                                    <option value="body_end">Body end (before &lt;/body&gt;)</option>
                                </select>
                            </div>
                            <div class="ui-field">
                                <x-label value="Consent category" />
                                <select class="ui-input" :name="`custom_snippets[${index}][consent_category]`" x-model="snippet.consent_category" required>
                                    <option value="analytics">Analytics</option>
                                    <option value="marketing">Marketing</option>
                                </select>
                            </div>
                            <div class="ui-field">
                                <x-label value="Display order" />
                                <input
                                    type="number"
                                    class="ui-input"
                                    min="0"
                                    max="999"
                                    :name="`custom_snippets[${index}][sort_order]`"
                                    x-model.number="snippet.sort_order"
                                >
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <label class="ui-checkbox">
                                <input type="hidden" :name="`custom_snippets[${index}][enabled]`" value="0">
                                <input
                                    type="checkbox"
                                    class="ui-checkbox__input"
                                    :name="`custom_snippets[${index}][enabled]`"
                                    value="1"
                                    x-model="snippet.enabled"
                                >
                                <span class="ui-checkbox__copy">
                                    <span class="ui-checkbox__label">Enabled</span>
                                </span>
                            </label>
                            <button type="button" class="admin-dash__panel-toggle" @click="removeSnippet(index)">Remove</button>
                        </div>

                        <div class="ui-field mt-3">
                            <x-label value="Code" />
                            <textarea
                                class="ui-input ui-textarea admin-snippet-editor"
                                rows="8"
                                :name="`custom_snippets[${index}][code]`"
                                x-model="snippet.code"
                                spellcheck="false"
                                required
                                placeholder="Paste the provider snippet exactly as supplied."
                            ></textarea>
                            <p class="ui-help">Rendered verbatim on the storefront. Do not paste server-side PHP.</p>
                        </div>
                    </div>
                </template>
            </div>
            @error('custom_snippets')
                <p class="ui-error mt-2" role="alert">{{ $message }}</p>
            @enderror
        </x-card>

        @if ($config['updated_at'] || $recentAudits->isNotEmpty())
            <x-card class="admin-form__section">
                <div class="admin-form__section-head">
                    <h2 class="admin-form__section-title">Change history</h2>
                    <p class="admin-form__section-desc">
                        @if ($config['updated_at'])
                            Last saved
                            <time datetime="{{ $config['updated_at'] }}">{{ \Illuminate\Support\Carbon::parse($config['updated_at'])->timezone(config('app.timezone'))->toDayDateTimeString() }}</time>
                            @if ($updatedBy)
                                by {{ $updatedBy->name }}
                            @endif
                        @else
                            No saves recorded yet.
                        @endif
                    </p>
                </div>
                @if ($recentAudits->isNotEmpty())
                    <ul class="admin-dash__activity">
                        @foreach ($recentAudits as $audit)
                            <li class="admin-dash__activity-item">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink">{{ $audit->summary }}</p>
                                    <p class="mt-0.5 text-sm text-ink-muted">
                                        {{ $audit->user?->name ?? 'Unknown admin' }}
                                    </p>
                                </div>
                                <time class="shrink-0 text-xs text-ink-subtle" datetime="{{ $audit->created_at?->toIso8601String() }}">
                                    {{ $audit->created_at?->diffForHumans() }}
                                </time>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        @endif

        <div class="admin-form__actions">
            <x-button type="submit" variant="primary">Save tracking settings</x-button>
            <x-button href="{{ route('admin.settings.edit') }}" variant="outline">Back to store settings</x-button>
        </div>
    </form>
@endsection

@push('head')
    <script>
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('trackingSnippetsEditor', (initial = []) => ({
                snippets: Array.isArray(initial) ? initial.map((snippet, index) => ({
                    id: snippet.id || (`tmp-${index}`),
                    name: snippet.name || '',
                    placement: snippet.placement || 'head',
                    code: snippet.code || '',
                    enabled: snippet.enabled !== false && snippet.enabled !== 0 && snippet.enabled !== '0',
                    sort_order: Number.isFinite(Number(snippet.sort_order)) ? Number(snippet.sort_order) : index,
                    consent_category: snippet.consent_category || 'marketing',
                })) : [],
                addSnippet() {
                    this.snippets.push({
                        id: `tmp-${Date.now()}`,
                        name: '',
                        placement: 'head',
                        code: '',
                        enabled: true,
                        sort_order: this.snippets.length,
                        consent_category: 'marketing',
                    });
                },
                removeSnippet(index) {
                    this.snippets.splice(index, 1);
                },
            }));
        });
    </script>
@endpush
