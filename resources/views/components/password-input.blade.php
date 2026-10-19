@props([
    'label' => null,
    'name' => 'password',
    'help' => null,
    'error' => null,
    'checklist' => false,
    'matchField' => null,
    'autocomplete' => 'current-password',
])

@php
    $id = $attributes->get('id') ?? str_replace(['[', ']'], ['-', ''], $name);
    $hasError = filled($error) || $errors->has($name);
    $errorMessage = $error ?? $errors->first($name);
@endphp

<div
    class="w-full"
    x-data="{
        show: false,
        value: @js(old($name, '')),
        other: '',
        matchField: @js($matchField),
        get hasLower() { return /[a-z]/.test(this.value) },
        get hasUpper() { return /[A-Z]/.test(this.value) },
        get hasNumber() { return /[0-9]/.test(this.value) },
        get hasSpecial() { return /[^A-Za-z0-9]/.test(this.value) },
        get hasLength() { return this.value.length >= 8 },
        get metCount() {
            return [this.hasLower, this.hasUpper, this.hasNumber, this.hasSpecial, this.hasLength].filter(Boolean).length
        },
        get mismatch() {
            return Boolean(this.matchField)
                && this.value.length > 0
                && this.other.length > 0
                && this.value !== this.other
        },
        syncOther() {
            if (! this.matchField) return;
            this.other = document.getElementById(this.matchField)?.value ?? '';
        },
        init() {
            if (! this.matchField) return;
            this.syncOther();
            const el = document.getElementById(this.matchField);
            el?.addEventListener('input', () => this.syncOther());
        },
    }"
>
    @if ($label)
        <x-label :for="$id" :value="$label" />
    @endif

    <div class="relative">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            :type="show ? 'text' : 'password'"
            autocomplete="{{ $autocomplete }}"
            x-model="value"
            @input="syncOther()"
            :class="mismatch ? 'ui-input-error' : ''"
            {{ $attributes->except(['id', 'class', 'type'])->merge([
                'class' => 'ui-input pe-11'.($hasError ? ' ui-input-error' : ''),
            ]) }}
        >

        <button
            type="button"
            class="absolute inset-y-0 end-0 inline-flex w-11 items-center justify-center text-ink-muted hover:text-ink"
            @click="show = !show"
            :aria-label="show ? 'Hide password' : 'Show password'"
            :aria-pressed="show.toString()"
        >
            <svg x-show="!show" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path d="M2.5 12S6.5 5.5 12 5.5 21.5 12 21.5 12 17.5 18.5 12 18.5 2.5 12 2.5 12Z" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="3.25"/>
            </svg>
            <svg x-show="show" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path d="M3 3l18 18M10.5 10.6a3.25 3.25 0 0 0 4.6 4.6M9.4 5.7A9.5 9.5 0 0 1 12 5.5C17.5 5.5 21.5 12 21.5 12a17 17 0 0 1-3.1 3.8M6.2 6.3A16.7 16.7 0 0 0 2.5 12S6.5 18.5 12 18.5c1.1 0 2.1-.2 3.1-.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>

    @if ($checklist)
        <div class="password-checklist" x-cloak x-show="value.length > 0" role="status" aria-live="polite">
            <div class="password-checklist__meter" aria-hidden="true">
                <span :class="metCount >= 1 ? 'is-on' : ''"></span>
                <span :class="metCount >= 2 ? 'is-on' : ''"></span>
                <span :class="metCount >= 3 ? 'is-on' : ''"></span>
                <span :class="metCount >= 4 ? 'is-on' : ''"></span>
                <span :class="metCount >= 5 ? 'is-on is-strong' : ''"></span>
            </div>
            <p class="password-checklist__hint" x-text="metCount < 5 ? 'Use a stronger password:' : 'Password looks good.'"></p>
            <ul class="password-checklist__list">
                <li :class="hasLength ? 'is-met' : ''">
                    <span class="password-checklist__icon" aria-hidden="true">
                        <svg viewBox="0 0 16 16" fill="none"><path d="M3.5 8.25 6.6 11.3 12.5 4.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    At least 8 characters
                </li>
                <li :class="hasUpper ? 'is-met' : ''">
                    <span class="password-checklist__icon" aria-hidden="true">
                        <svg viewBox="0 0 16 16" fill="none"><path d="M3.5 8.25 6.6 11.3 12.5 4.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    One uppercase letter
                </li>
                <li :class="hasLower ? 'is-met' : ''">
                    <span class="password-checklist__icon" aria-hidden="true">
                        <svg viewBox="0 0 16 16" fill="none"><path d="M3.5 8.25 6.6 11.3 12.5 4.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    One lowercase letter
                </li>
                <li :class="hasNumber ? 'is-met' : ''">
                    <span class="password-checklist__icon" aria-hidden="true">
                        <svg viewBox="0 0 16 16" fill="none"><path d="M3.5 8.25 6.6 11.3 12.5 4.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    One number
                </li>
                <li :class="hasSpecial ? 'is-met' : ''">
                    <span class="password-checklist__icon" aria-hidden="true">
                        <svg viewBox="0 0 16 16" fill="none"><path d="M3.5 8.25 6.6 11.3 12.5 4.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    One special character (!@#$…)
                </li>
            </ul>
        </div>
    @endif

    @if ($matchField)
        <p
            class="ui-error"
            x-cloak
            x-show="mismatch"
            role="alert"
        >Passwords do not match.</p>
    @endif

    @if ($help && ! $errorMessage && ! $checklist)
        <p class="ui-help">{{ $help }}</p>
    @endif

    @if ($errorMessage)
        <p class="ui-error" role="alert" x-show="!mismatch">{{ $errorMessage }}</p>
    @endif
</div>
