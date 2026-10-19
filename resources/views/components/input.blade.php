@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'help' => null,
    'error' => null,
])

@php
    $id = $attributes->get('id') ?? ($name ? str_replace(['[', ']'], ['-', ''], $name) : 'input-'.uniqid());
    $hasError = filled($error) || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if ($label)
        <x-label :for="$id" :value="$label" />
    @endif

    <input
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        type="{{ $type }}"
        {{ $attributes->except('class')->merge([
            'class' => 'ui-input'.($hasError ? ' ui-input-error' : ''),
        ]) }}
    >

    @if ($help && ! $errorMessage)
        <p class="ui-help">{{ $help }}</p>
    @endif

    @if ($errorMessage)
        <p class="ui-error" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
