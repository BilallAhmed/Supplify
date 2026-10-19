@props([
    'variant' => 'info',
    'dismissible' => false,
    'title' => null,
    'autohide' => null,
])

@php
    $styles = [
        'info' => 'border-info/20 bg-info-soft text-info',
        'success' => 'border-success/20 bg-success-soft text-success',
        'warning' => 'border-warning/20 bg-warning-soft text-warning',
        'danger' => 'border-danger/20 bg-danger-soft text-danger',
    ];
    $autohideMs = filled($autohide) ? (int) $autohide : null;
@endphp

<div
    x-data="{
        show: false,
        init() {
            this.$nextTick(() => { this.show = true });
            @if ($autohideMs)
                setTimeout(() => { this.show = false }, {{ $autohideMs }});
            @endif
        },
    }"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-5"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-250"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-4"
    role="alert"
    {{ $attributes->merge([
        'class' => 'relative block w-full max-w-none rounded-[var(--radius-panel)] border px-4 py-3 text-sm shadow-soft '.($styles[$variant] ?? $styles['info']),
    ]) }}
>
    <div class="flex items-start gap-3 {{ $dismissible ? 'pr-8' : '' }}">
        <div class="min-w-0 flex-1">
            @if ($title)
                <p class="font-semibold">{{ $title }}</p>
            @endif
            <div class="@if($title) mt-1 @endif leading-relaxed">{{ $slot }}</div>
        </div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            class="absolute top-2.5 right-2.5 rounded p-1 opacity-70 hover:opacity-100"
            @click="show = false"
            aria-label="Dismiss"
        >
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
            </svg>
        </button>
    @endif
</div>
