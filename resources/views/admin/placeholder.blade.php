@extends('layouts.admin')

@section('title', $title)
@section('breadcrumb', $title)

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ $title }}</h1>
        <p class="mt-1 text-sm text-ink-muted">{{ $description }}</p>
    </div>
    <x-badge variant="accent">Module {{ $module }}</x-badge>
@endsection

@section('content')
    <div class="rounded-[var(--radius-panel)] border border-dashed border-line-strong bg-surface-elevated p-8 text-center shadow-soft sm:p-12">
        <p class="text-xs font-semibold tracking-[0.16em] text-brand uppercase">Coming next</p>
        <h2 class="mt-3 font-display text-xl font-bold text-ink">{{ $title }} management is not built yet</h2>
        <p class="mx-auto mt-2 max-w-lg text-sm text-ink-muted">
            This navigation item is reserved so the admin shell is complete. Full CRUD arrives in Module {{ $module }}.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <x-button href="{{ route('admin.dashboard') }}" variant="primary">Back to dashboard</x-button>
        </div>
    </div>
@endsection
