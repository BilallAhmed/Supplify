@props([
    'label' => null,
    'name' => null,
    'help' => null,
    'error' => null,
])

@php
    $id = $attributes->get('id') ?? ($name ? str_replace(['[', ']'], ['-', ''], $name) : 'select-'.uniqid());
    $hasError = filled($error) || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div class="w-full">
    @if ($label)
        <x-label :for="$id" :value="$label" />
    @endif

    <select
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->merge([
            'class' => 'ui-input'.($hasError ? ' ui-input-error' : ''),
        ]) }}
    >
        {{ $slot }}
    </select>

    @if ($help && ! $errorMessage)
        <p class="ui-help">{{ $help }}</p>
    @endif

    @if ($errorMessage)
        <p class="ui-error" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
