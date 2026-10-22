@props([
    'name',
    'value' => null,
    'label' => 'Image',
    'help' => null,
    'required' => false,
    'previewUrl' => null,
    'fieldId' => null,
    'useOld' => true,
])

@php
    $dotName = rtrim(str_replace(['][', '[', ']'], ['.', '.', ''], (string) $name), '.');
    $value = $useOld ? old($dotName, $value) : $value;
    $value = is_string($value) ? $value : '';
    $resolvedUrl = $previewUrl ?: (\App\Support\MediaUrl::toUrl($value) ?: '');
    $filename = $value ? basename(str_replace('\\', '/', $value)) : '';
    $absolute = \App\Support\MediaUrl::absolutePath($value);
    $size = $absolute ? @getimagesize($absolute) : false;
    $dimensions = is_array($size) ? ($size[0].'×'.$size[1]) : '';
    $fieldId = $fieldId ?: 'mp-'.md5($name);
@endphp

<div
    class="media-picker-field"
    x-data="mediaPickerField({
        name: @js($name),
        value: @js($value),
        previewUrl: @js($resolvedUrl),
        filename: @js($filename),
        dimensions: @js($dimensions),
        label: @js($label),
        help: @js($help),
        required: @js((bool) $required),
        fieldId: @js($fieldId),
    })"
>
    @if ($label)
        <x-label :value="$label" class="mb-1.5" />
    @endif

    <input type="hidden" :name="name" x-model="value" @if ($required) :required="!value" @endif>

    <div class="media-picker-field__card">
        <div class="media-picker-field__preview">
            <template x-if="previewUrl">
                <img :src="previewUrl" :alt="filename || 'Selected image'" class="media-picker-field__thumb">
            </template>
            <template x-if="!previewUrl">
                <div class="media-picker-field__empty" aria-hidden="true">
                    <svg class="size-8 text-ink-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                    </svg>
                </div>
            </template>
        </div>

        <div class="media-picker-field__meta">
            <p class="media-picker-field__name" x-text="filename || 'No image selected'"></p>
            <p class="media-picker-field__dims text-xs text-ink-subtle" x-show="dimensions" x-text="dimensions"></p>

            <div class="media-picker-field__actions">
                <template x-if="!value">
                    <x-button type="button" variant="outline" size="sm" @click="openLibrary()">Select image</x-button>
                </template>
                <template x-if="value">
                    <div class="flex flex-wrap gap-2">
                        <x-button type="button" variant="outline" size="sm" @click="openLibrary()">Replace</x-button>
                        <x-button type="button" variant="ghost" size="sm" @click="clear()">Remove</x-button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @if ($help)
        <p class="ui-help mt-1.5">{{ $help }}</p>
    @endif

    @error($name)
        <p class="ui-error" role="alert">{{ $message }}</p>
    @enderror
</div>
