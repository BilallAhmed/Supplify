@props([
    'label' => null,
    'name' => null,
    'value' => '1',
    'help' => null,
    'error' => null,
    'checked' => false,
])

@php
    $id = $attributes->get('id') ?? ($name ? str_replace(['[', ']'], ['-', ''], $name) : 'checkbox-'.uniqid());
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div class="w-full">
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3">
        <input
            id="{{ $id }}"
            type="checkbox"
            value="{{ $value }}"
            @if ($name) name="{{ $name }}" @endif
            @checked((bool) $checked)
            {{ $attributes->except('checked')->merge([
                'class' => 'mt-1 size-4 rounded border-line text-brand focus:ring-brand/30',
            ]) }}
        >
        <span class="min-w-0">
            @if ($label)
                <span class="block text-sm font-medium text-ink">{{ $label }}</span>
            @else
                <span class="block text-sm text-ink">{{ $slot }}</span>
            @endif
            @if ($help)
                <span class="mt-0.5 block text-xs text-ink-muted">{{ $help }}</span>
            @endif
        </span>
    </label>

    @if ($errorMessage)
        <p class="ui-error" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
