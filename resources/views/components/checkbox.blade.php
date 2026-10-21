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

<div {{ $attributes->only('class')->merge(['class' => 'ui-field ui-field--checkbox']) }}>
    <label for="{{ $id }}" class="ui-checkbox">
        <input
            id="{{ $id }}"
            type="checkbox"
            value="{{ $value }}"
            @if ($name) name="{{ $name }}" @endif
            @checked((bool) $checked)
            {{ $attributes->except(['class', 'checked'])->merge([
                'class' => 'ui-checkbox__input',
            ]) }}
        >
        <span class="ui-checkbox__copy">
            @if ($label)
                <span class="ui-checkbox__label">{{ $label }}</span>
            @else
                <span class="ui-checkbox__label">{{ $slot }}</span>
            @endif
            @if ($help)
                <span class="ui-checkbox__help">{{ $help }}</span>
            @endif
        </span>
    </label>

    @if ($errorMessage)
        <p class="ui-error" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
