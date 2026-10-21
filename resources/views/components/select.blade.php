@props([
    'label' => null,
    'name' => null,
    'help' => null,
    'error' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id') ?? ($name ? str_replace(['[', ']'], ['-', ''], $name) : 'select-'.uniqid());
    $hasError = filled($error) || ($name && $errors->has($name));
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
    $isRequired = $required || $attributes->has('required');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'ui-field']) }}>
    @if ($label)
        <x-label :for="$id" :value="$label" :required="$isRequired" />
    @endif

    <select
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($isRequired) required @endif
        {{ $attributes->except(['class', 'required'])->merge([
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
