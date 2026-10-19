@props([
    'name' => 'leaf',
])

@php
    $paths = [
        'energy' => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" stroke-linejoin="round"/>',
        'sleep' => '<path d="M12 3a9 9 0 1 0 9 9c0-.4 0-.8-.1-1.2A7 7 0 0 1 12 3Z" stroke-linejoin="round"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4.2 2.8 7.2 7 8.5 4.2-1.3 7-4.3 7-8.5V6l-7-3Z" stroke-linejoin="round"/><path d="m9.5 12 1.8 1.8L15 10" stroke-linecap="round" stroke-linejoin="round"/>',
        'gut' => '<path d="M8 4c-2.2 1.5-3.5 3.8-3.5 6.5C4.5 15 8 18 12 20c4-2 7.5-5 7.5-9.5C19.5 7.8 18.2 5.5 16 4" stroke-linecap="round"/><path d="M9 9.5c1.2 1.8 2.2 1.8 3.5 0s2.3-1.8 3.5 0" stroke-linecap="round"/>',
        'leaf' => '<path d="M5 19c6-1 10-5 12-12 0 0-6-1-11 4-3 3-3 6.5-1 8Z" stroke-linejoin="round"/><path d="M9 15c2-2 4.5-3.5 8-4" stroke-linecap="round"/>',
        'flask' => '<path d="M9 3h6M10 3v5.2L6.4 16A3 3 0 0 0 9 20.5h6a3 3 0 0 0 2.6-4.5L14 8.2V3" stroke-linejoin="round"/><path d="M8.2 14h7.6" stroke-linecap="round"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke-linejoin="round"/>',
        'focus' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4" stroke-linecap="round"/>',
        'package' => '<path d="m3.5 8 8.5-4.5L20.5 8v8L12 20.5 3.5 16V8Z" stroke-linejoin="round"/><path d="M12 12v8.5M3.5 8 12 12l8.5-4" stroke-linejoin="round"/>',
    ];
    $markup = $paths[$name] ?? $paths['leaf'];
@endphp

<svg
    {{ $attributes->class('goal-icon') }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.5"
    aria-hidden="true"
>{!! $markup !!}</svg>
