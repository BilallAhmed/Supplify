@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'left',
])

@php
    $alignClass = $align === 'center' ? 'mx-auto text-center' : 'text-left';
@endphp

<div {{ $attributes->merge(['class' => 'max-w-2xl '.$alignClass]) }}>
    @if ($eyebrow)
        <p class="mb-2 text-xs font-semibold tracking-[0.18em] text-brand uppercase">{{ $eyebrow }}</p>
    @endif

    <h2 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{{ $title }}</h2>

    @if ($description)
        <p class="ui-prose-muted mt-3">{{ $description }}</p>
    @endif
</div>
