@props([
    'name' => 'modal',
    'title' => null,
    'maxWidth' => 'lg',
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ];
    $maxWidthClass = $widths[$maxWidth] ?? $widths['lg'];
@endphp

<div
    x-data="{
        open: false,
        init() {
            this.$watch('open', value => {
                document.body.classList.toggle('overflow-hidden', value);
            });
            window.addEventListener('open-modal-{{ $name }}', () => { this.open = true });
            window.addEventListener('close-modal-{{ $name }}', () => { this.open = false });
        },
        close() {
            this.open = false;
            window.dispatchEvent(new CustomEvent('close-modal-{{ $name }}'));
        }
    }"
    x-show="open"
    x-cloak
    class="relative z-50"
    @keydown.escape.window="close()"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-labelledby="modal-title-{{ $name }}" @endif
>
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 bg-ink/50"
        @click="close()"
        aria-hidden="true"
    ></div>

    <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="relative w-full {{ $maxWidthClass }} rounded-[var(--radius-panel)] border border-line bg-surface-elevated p-5 shadow-lift sm:p-6"
                @click.stop
            >
                <div class="mb-4 flex items-start justify-between gap-4">
                    @if ($title)
                        <h2 id="modal-title-{{ $name }}" class="font-display text-xl font-bold text-ink">
                            {{ $title }}
                        </h2>
                    @else
                        <span></span>
                    @endif

                    <button
                        type="button"
                        class="rounded p-1 text-ink-muted hover:bg-surface-muted hover:text-ink"
                        @click="close()"
                        aria-label="Close dialog"
                    >
                        <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                        </svg>
                    </button>
                </div>

                <div>{{ $slot }}</div>

                @isset($footer)
                    <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-line pt-4">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>
    </div>
</div>
