@props([
    'label' => null,
    'name' => null,
    'help' => null,
    'error' => null,
    'rows' => 4,
])

@php
    $id = $attributes->get('id') ?? ($name ? str_replace(['[', ']'], ['-', ''], $name) : 'textarea-'.uniqid());
    $hasError = filled($error) || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div class="w-full">
    @if ($label)
        <x-label :for="$id" :value="$label" />
    @endif

    <textarea
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        rows="{{ $rows }}"
        {{ $attributes->merge([
            'class' => 'ui-input min-h-24'.($hasError ? ' ui-input-error' : ''),
        ]) }}
    >{{ $slot }}</textarea>

    @if ($help && ! $errorMessage)
        <p class="ui-help">{{ $help }}</p>
    @endif

    @if ($errorMessage)
        <p class="ui-error" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
