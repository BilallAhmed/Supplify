@php
    $items = $section->config['items'] ?? [];
@endphp

<section class="ui-section border-t border-line/40 bg-surface-elevated" aria-labelledby="testimonials-heading">
    <x-container>
        <div>
            <x-section-heading
                id="testimonials-heading"
                :eyebrow="$section->subtitle"
                :title="$section->title ?: 'From the community'"
            />
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-3">
            @foreach ($items as $item)
                <blockquote class="rounded-[var(--radius-panel)] border border-line/70 bg-brand-mist/40 p-6 shadow-soft">
                    <p class="text-base leading-relaxed text-ink">“{{ $item['quote'] ?? '' }}”</p>
                    <footer class="mt-4 text-sm font-medium text-brand">{{ $item['author'] ?? '' }}</footer>
                </blockquote>
            @endforeach
        </div>
    </x-container>
</section>
