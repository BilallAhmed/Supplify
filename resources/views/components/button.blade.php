@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'tag' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-medium transition duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:pointer-events-none disabled:opacity-50';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs rounded-[var(--radius-control)]',
        'md' => 'px-4 py-2.5 text-sm rounded-[var(--radius-control)]',
        'lg' => 'px-6 py-3 text-base rounded-[var(--radius-control)]',
    ];

    $variants = [
        'primary' => 'bg-brand text-white hover:bg-brand-hover shadow-soft',
        'secondary' => 'bg-surface-deep text-white hover:bg-ink',
        'outline' => 'border border-line-strong bg-transparent text-ink hover:border-brand hover:text-brand',
        'ghost' => 'bg-transparent text-ink hover:bg-brand-mist hover:text-brand',
        'accent' => 'bg-accent text-ink hover:brightness-95',
        'danger' => 'bg-danger text-white hover:brightness-95',
    ];

    $classes = trim($base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']).' '.$attributes->get('class'));
@endphp

@if ($href || $tag === 'a')
    <a href="{{ $href ?? '#' }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
