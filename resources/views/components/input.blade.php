@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'help' => null,
    'error' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id') ?? ($name ? str_replace(['[', ']'], ['-', ''], $name) : 'input-'.uniqid());
    $hasError = filled($error) || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
    $isRequired = $required || $attributes->has('required');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'ui-field']) }}>
    @if ($label)
        <x-label :for="$id" :value="$label" :required="$isRequired" />
    @endif

    <input
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        type="{{ $type }}"
        @if ($isRequired) required @endif
        {{ $attributes->except(['class', 'required'])->merge([
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
