@extends('layouts.storefront')

@section('title', 'Design System — '.config('app.name'))
@section('meta_description', 'Internal UI foundation reference for the supplement storefront.')
@section('canonical', route('design-system'))

@section('content')
    <x-container class="ui-section space-y-16">
        <header class="max-w-3xl">
            <p class="mb-2 text-xs font-semibold tracking-[0.18em] text-brand uppercase">Module 01</p>
            <h1 class="font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">Design system</h1>
            <p class="ui-prose-muted mt-4">
                Reusable storefront primitives: typography, controls, feedback, and layout shells.
                Use these components instead of one-off styles.
            </p>
        </header>

        {{-- Typography --}}
        <section aria-labelledby="ds-type">
            <h2 id="ds-type" class="font-display text-2xl font-bold text-ink">Typography</h2>
            <div class="mt-6 space-y-4 rounded-[var(--radius-panel)] border border-line bg-surface-elevated p-6">
                <p class="font-display text-4xl font-extrabold tracking-tight">Display / Plus Jakarta Sans</p>
                <p class="text-lg text-ink">Body / Figtree — readable product and policy copy.</p>
                <p class="text-sm text-ink-muted">Muted supporting text for descriptions and help copy.</p>
                <p class="text-xs tracking-[0.18em] text-brand uppercase">Eyebrow label style</p>
            </div>
        </section>

        {{-- Buttons --}}
        <section aria-labelledby="ds-buttons">
            <h2 id="ds-buttons" class="font-display text-2xl font-bold text-ink">Buttons</h2>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-button variant="primary">Primary</x-button>
                <x-button variant="secondary">Secondary</x-button>
                <x-button variant="outline">Outline</x-button>
                <x-button variant="ghost">Ghost</x-button>
                <x-button variant="accent">Accent</x-button>
                <x-button variant="danger">Danger</x-button>
                <x-button variant="primary" size="sm">Small</x-button>
                <x-button variant="primary" size="lg">Large</x-button>
                <x-button variant="primary" disabled>Disabled</x-button>
            </div>
        </section>

        {{-- Forms --}}
        <section aria-labelledby="ds-forms">
            <h2 id="ds-forms" class="font-display text-2xl font-bold text-ink">Forms</h2>
            <x-card class="mt-6 max-w-xl space-y-4">
                <x-input label="Email" name="demo_email" type="email" placeholder="you@example.com" help="We'll never share your email." />
                <x-input label="With error" name="demo_error" error="This field is required." />
                <x-select label="Goal" name="demo_goal">
                    <option value="">Select a goal</option>
                    <option>Energy</option>
                    <option>Sleep</option>
                    <option>Immunity</option>
                </x-select>
                <x-textarea label="Message" name="demo_message" placeholder="How can we help?" />
                <x-checkbox name="demo_terms" label="I agree to the terms" help="Required before checkout." />
                <x-button type="button" variant="primary">Submit sample</x-button>
            </x-card>
        </section>

        {{-- Alerts & badges --}}
        <section aria-labelledby="ds-feedback" class="space-y-4">
            <h2 id="ds-feedback" class="font-display text-2xl font-bold text-ink">Alerts & badges</h2>
            <x-alert variant="info" title="Info">Product details updated successfully.</x-alert>
            <x-alert variant="success" :dismissible="true">Order placed — confirmation email queued.</x-alert>
            <x-alert variant="warning">Low stock on featured SKU.</x-alert>
            <x-alert variant="danger">Payment could not be verified.</x-alert>
            <div class="flex flex-wrap gap-2 pt-2">
                <x-badge>Neutral</x-badge>
                <x-badge variant="brand">In stock</x-badge>
                <x-badge variant="accent">Featured</x-badge>
                <x-badge variant="success">Paid</x-badge>
                <x-badge variant="warning">Low stock</x-badge>
                <x-badge variant="danger">Out of stock</x-badge>
            </div>
        </section>

        {{-- Cards --}}
        <section aria-labelledby="ds-cards">
            <h2 id="ds-cards" class="font-display text-2xl font-bold text-ink">Cards</h2>
            <p class="ui-prose-muted mt-2 max-w-2xl">Use cards for interactive product/content units — not as decorative wrappers for every section.</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-card :hover="true">
                    <x-badge variant="brand">Product</x-badge>
                    <h3 class="mt-3 font-display text-lg font-bold">Omega Complex</h3>
                    <p class="mt-2 text-sm text-ink-muted">Placeholder product card structure for Module 05.</p>
                    <div class="mt-4">
                        <x-button size="sm" variant="outline">View details</x-button>
                    </div>
                </x-card>
                <x-card>
                    <h3 class="font-display text-lg font-bold">Static panel</h3>
                    <p class="mt-2 text-sm text-ink-muted">Non-hover card for dense admin-adjacent storefront UI.</p>
                </x-card>
            </div>
        </section>

        {{-- Modal --}}
        <section aria-labelledby="ds-modal">
            <h2 id="ds-modal" class="font-display text-2xl font-bold text-ink">Modal</h2>
            <div class="mt-6">
                <x-button
                    type="button"
                    variant="secondary"
                    @click="$dispatch('open-modal-sample')"
                >
                    Open sample modal
                </x-button>
            </div>

            <x-modal name="sample" title="Sample dialog">
                <p class="text-sm text-ink-muted">
                    Modals are for focused interactions (confirmations, quick forms). Escape and backdrop click dismiss.
                </p>
                <x-slot:footer>
                    <x-button type="button" variant="ghost" @click="$dispatch('close-modal-sample')">Cancel</x-button>
                    <x-button type="button" variant="primary" @click="$dispatch('close-modal-sample')">Confirm</x-button>
                </x-slot:footer>
            </x-modal>
        </section>

        {{-- Spacing / breakpoints note --}}
        <section aria-labelledby="ds-layout">
            <h2 id="ds-layout" class="font-display text-2xl font-bold text-ink">Layout & spacing</h2>
            <x-card class="mt-6">
                <ul class="space-y-2 text-sm text-ink-muted">
                    <li><strong class="text-ink">Container:</strong> <code class="text-brand">ui-container</code> / <code class="text-brand">&lt;x-container&gt;</code> — max-width 7xl with responsive gutters.</li>
                    <li><strong class="text-ink">Sections:</strong> <code class="text-brand">ui-section</code> — vertical rhythm py-12 → py-20.</li>
                    <li><strong class="text-ink">Breakpoints:</strong> Tailwind defaults (sm 40rem, md 48rem, lg 64rem, xl 80rem) + theme <code class="text-brand">xs</code> 30rem.</li>
                    <li><strong class="text-ink">Motion:</strong> <code class="text-brand">prefers-reduced-motion</code> respected globally in base CSS.</li>
                </ul>
            </x-card>
        </section>
    </x-container>
@endsection
