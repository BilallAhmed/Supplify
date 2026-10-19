@php
    /** @var array<string, mixed> $about */
    $hero = $about['hero'] ?? [];
    $purpose = $about['purpose'] ?? [];
    $principles = $about['principles'] ?? [];
    $journey = $about['journey'] ?? [];
    $essentials = $about['essentials'] ?? [];
    $everyday = $about['everyday'] ?? [];
    $cta = $about['cta'] ?? [];
    $team = $about['team'] ?? [];
    $connectionBlocks = $essentials['blocks'] ?? $essentials['rows'] ?? [];
@endphp

@php
    $aboutErrorOpen = fn (string $prefix) => collect($errors->keys())->contains(
        fn ($k) => str_starts_with((string) $k, $prefix)
    );
@endphp

<div class="admin-panel admin-panel--sections space-y-5">
    <div>
        <h2 class="admin-panel__title">About page sections</h2>
        <p class="admin-panel__intro">
            Editorial content for the dedicated About template. Choose images from the media library
            (or upload new ones). Leave empty to use the default slot files when present.
        </p>
    </div>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" open>
        <summary class="cursor-pointer font-semibold text-ink">1. Hero banner</summary>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <x-input label="Eyebrow" name="about[hero][eyebrow]" :value="old('about.hero.eyebrow', $hero['eyebrow'] ?? '')" />
            <x-input label="CTA label" name="about[hero][cta_label]" :value="old('about.hero.cta_label', $hero['cta_label'] ?? '')" />
            <div class="sm:col-span-2">
                <x-input label="Heading" name="about[hero][heading]" :value="old('about.hero.heading', $hero['heading'] ?? '')" />
            </div>
            <div class="sm:col-span-2">
                <x-textarea label="Intro" name="about[hero][intro]" rows="3">{{ old('about.hero.intro', $hero['intro'] ?? '') }}</x-textarea>
            </div>
            <x-input label="CTA href" name="about[hero][cta_href]" :value="old('about.hero.cta_href', $hero['cta_href'] ?? '#about-approach')" help="Default: #about-approach" />
            <div class="sm:col-span-2">
                <x-media-picker name="about[hero][image]" label="Hero image" :value="old('about.hero.image', $hero['image'] ?? '')" />
            </div>
            <x-input label="Image alt" name="about[hero][image_alt]" :value="old('about.hero.image_alt', $hero['image_alt'] ?? '')" help="Describe the scene; do not claim pictured people are staff." />
            <x-input label="Focal point" name="about[hero][focal]" :value="old('about.hero.focal', $hero['focal'] ?? 'right')" help="center, left, right, top, bottom" />
            <input type="hidden" name="about[hero][enabled]" value="1">
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" @if ($aboutErrorOpen('about.purpose')) open @endif>
        <summary class="cursor-pointer font-semibold text-ink">2. Beginning / purpose</summary>
        <div class="mt-3 space-y-3">
            <x-input label="Heading" name="about[purpose][heading]" :value="old('about.purpose.heading', $purpose['heading'] ?? '')" />
            <x-textarea label="Paragraph 1" name="about[purpose][paragraph_1]" rows="3">{{ old('about.purpose.paragraph_1', $purpose['paragraph_1'] ?? '') }}</x-textarea>
            <x-textarea label="Paragraph 2" name="about[purpose][paragraph_2]" rows="3">{{ old('about.purpose.paragraph_2', $purpose['paragraph_2'] ?? '') }}</x-textarea>
            <x-textarea label="Paragraph 3" name="about[purpose][paragraph_3]" rows="3">{{ old('about.purpose.paragraph_3', $purpose['paragraph_3'] ?? '') }}</x-textarea>
            <x-input label="Pull statement (optional)" name="about[purpose][pull_quote]" :value="old('about.purpose.pull_quote', $purpose['pull_quote'] ?? '')" />
            <x-media-picker name="about[purpose][image]" label="Story image" :value="old('about.purpose.image', $purpose['image'] ?? '')" />
            <x-input label="Image alt" name="about[purpose][image_alt]" :value="old('about.purpose.image_alt', $purpose['image_alt'] ?? '')" />
            <input type="hidden" name="about[purpose][enabled]" value="1">
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" @if ($aboutErrorOpen('about.principles')) open @endif>
        <summary class="cursor-pointer font-semibold text-ink">3. Principles</summary>
        <div class="mt-3 space-y-3">
            <x-input label="Heading" name="about[principles][heading]" :value="old('about.principles.heading', $principles['heading'] ?? '')" />
            <x-input label="Intro (optional)" name="about[principles][intro]" :value="old('about.principles.intro', $principles['intro'] ?? '')" />
            @foreach (($principles['items'] ?? []) as $i => $item)
                <div class="grid gap-2 rounded border border-line/70 p-3 sm:grid-cols-3">
                    <x-input label="Icon" name="about[principles][items][{{ $i }}][icon]" :value="old("about.principles.items.$i.icon", $item['icon'] ?? 'leaf')" help="focus, leaf, heart, flask…" />
                    <x-input label="Title" name="about[principles][items][{{ $i }}][title]" :value="old("about.principles.items.$i.title", $item['title'] ?? '')" />
                    <div class="sm:col-span-3">
                        <x-textarea label="Description" name="about[principles][items][{{ $i }}][body]" rows="2">{{ old("about.principles.items.$i.body", $item['body'] ?? '') }}</x-textarea>
                    </div>
                </div>
            @endforeach
            <input type="hidden" name="about[principles][enabled]" value="1">
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" @if ($aboutErrorOpen('about.journey')) open @endif>
        <summary class="cursor-pointer font-semibold text-ink">4. Journey stages</summary>
        <div class="mt-3 space-y-3">
            <x-input label="Heading" name="about[journey][heading]" :value="old('about.journey.heading', $journey['heading'] ?? '')" />
            <x-textarea label="Intro" name="about[journey][intro]" rows="2">{{ old('about.journey.intro', $journey['intro'] ?? '') }}</x-textarea>
            @foreach (($journey['stages'] ?? []) as $i => $stage)
                <div class="grid gap-2 rounded border border-line/70 p-3 sm:grid-cols-2">
                    <x-input label="Number" name="about[journey][stages][{{ $i }}][number]" :value="old("about.journey.stages.$i.number", $stage['number'] ?? '')" />
                    <x-input label="Title" name="about[journey][stages][{{ $i }}][title]" :value="old("about.journey.stages.$i.title", $stage['title'] ?? '')" />
                    <div class="sm:col-span-2">
                        <x-textarea label="Body" name="about[journey][stages][{{ $i }}][body]" rows="3">{{ old("about.journey.stages.$i.body", $stage['body'] ?? '') }}</x-textarea>
                    </div>
                    <x-input label="Image slot key" name="about[journey][stages][{{ $i }}][slot]" :value="old("about.journey.stages.$i.slot", $stage['slot'] ?? '')" />
                    <div class="sm:col-span-2">
                        <x-media-picker name="about[journey][stages][{{ $i }}][image]" label="Stage image" :value="old("about.journey.stages.$i.image", $stage['image'] ?? '')" />
                    </div>
                    <x-input label="Image alt" name="about[journey][stages][{{ $i }}][image_alt]" :value="old("about.journey.stages.$i.image_alt", $stage['image_alt'] ?? '')" />
                </div>
            @endforeach
            <input type="hidden" name="about[journey][enabled]" value="1">
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" @if ($aboutErrorOpen('about.essentials')) open @endif>
        <summary class="cursor-pointer font-semibold text-ink">5. USA–Pakistan connections</summary>
        <div class="mt-3 space-y-3">
            <x-input label="Heading" name="about[essentials][heading]" :value="old('about.essentials.heading', $essentials['heading'] ?? '')" />
            <x-textarea label="Introduction" name="about[essentials][intro]" rows="2">{{ old('about.essentials.intro', $essentials['intro'] ?? '') }}</x-textarea>
            <x-media-picker name="about[essentials][image]" label="Section image" :value="old('about.essentials.image', $essentials['image'] ?? $essentials['product_image'] ?? '')" />
            <x-input label="Section image alt" name="about[essentials][image_alt]" :value="old('about.essentials.image_alt', $essentials['image_alt'] ?? '')" help="Describe as a conceptual composite when appropriate." />
            @foreach ($connectionBlocks as $i => $block)
                <div class="space-y-2 rounded border border-line/70 p-3">
                    <x-input label="Block title" name="about[essentials][blocks][{{ $i }}][title]" :value="old("about.essentials.blocks.$i.title", $block['title'] ?? '')" />
                    <x-textarea label="Block body" name="about[essentials][blocks][{{ $i }}][body]" rows="3">{{ old("about.essentials.blocks.$i.body", $block['body'] ?? '') }}</x-textarea>
                </div>
            @endforeach
            <x-textarea label="Closing paragraph" name="about[essentials][closing]" rows="2">{{ old('about.essentials.closing', $essentials['closing'] ?? '') }}</x-textarea>
            <input type="hidden" name="about[essentials][enabled]" value="1">
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" @if ($aboutErrorOpen('about.everyday')) open @endif>
        <summary class="cursor-pointer font-semibold text-ink">6. Looking ahead</summary>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input label="Heading" name="about[everyday][heading]" :value="old('about.everyday.heading', $everyday['heading'] ?? '')" />
            </div>
            <div class="sm:col-span-2">
                <x-textarea label="Paragraph 1" name="about[everyday][body]" rows="3">{{ old('about.everyday.body', $everyday['body'] ?? '') }}</x-textarea>
            </div>
            <div class="sm:col-span-2">
                <x-textarea label="Paragraph 2" name="about[everyday][body_2]" rows="3">{{ old('about.everyday.body_2', $everyday['body_2'] ?? '') }}</x-textarea>
            </div>
            <x-input label="Optional link label" name="about[everyday][link_label]" :value="old('about.everyday.link_label', $everyday['link_label'] ?? '')" />
            <x-input label="Optional link URL" name="about[everyday][link_url]" :value="old('about.everyday.link_url', $everyday['link_url'] ?? '')" />
            <div class="sm:col-span-2">
                <x-media-picker name="about[everyday][image]" label="Lifestyle image" :value="old('about.everyday.image', $everyday['image'] ?? '')" />
            </div>
            <x-input label="Image alt" name="about[everyday][image_alt]" :value="old('about.everyday.image_alt', $everyday['image_alt'] ?? '')" />
            <input type="hidden" name="about[everyday][enabled]" value="1">
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" @if ($aboutErrorOpen('about.cta')) open @endif>
        <summary class="cursor-pointer font-semibold text-ink">7. Closing CTA</summary>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input label="Heading" name="about[cta][heading]" :value="old('about.cta.heading', $cta['heading'] ?? '')" />
            </div>
            <div class="sm:col-span-2">
                <x-textarea label="Body" name="about[cta][body]" rows="2">{{ old('about.cta.body', $cta['body'] ?? '') }}</x-textarea>
            </div>
            <x-input label="Primary label" name="about[cta][primary_label]" :value="old('about.cta.primary_label', $cta['primary_label'] ?? '')" />
            <x-input label="Primary URL" name="about[cta][primary_url]" :value="old('about.cta.primary_url', $cta['primary_url'] ?? '/shop')" />
            <x-input label="Secondary label (optional)" name="about[cta][secondary_label]" :value="old('about.cta.secondary_label', $cta['secondary_label'] ?? '')" />
            <x-input label="Secondary URL (optional)" name="about[cta][secondary_url]" :value="old('about.cta.secondary_url', $cta['secondary_url'] ?? '')" />
            <input type="hidden" name="about[cta][enabled]" value="1">
        </div>
    </details>

    <details class="admin-section-details rounded-[var(--radius-control)] border border-line bg-surface-elevated p-3" @if ($aboutErrorOpen('about.team')) open @endif>
        <summary class="cursor-pointer font-semibold text-ink">Optional team module</summary>
        <div class="mt-3 space-y-3">
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="about[team][enabled]" value="0">
                <input type="checkbox" name="about[team][enabled]" value="1" class="size-4 rounded border-line text-brand" @checked((bool) old('about.team.enabled', $team['enabled'] ?? false))>
                Enable team section (stays hidden when empty/disabled)
            </label>
            <x-input label="Heading" name="about[team][heading]" :value="old('about.team.heading', $team['heading'] ?? '')" />
            <p class="text-xs text-ink-subtle">Keep disabled until genuine photographs are available.</p>
        </div>
    </details>
</div>
