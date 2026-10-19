@php $isEdit = $product->exists; @endphp

@extends('layouts.admin')

@section('title', $isEdit ? 'Edit product' : 'Add product')
@section('breadcrumb', $isEdit ? 'Products / Edit' : 'Products / New')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
            {{ $isEdit ? 'Edit product' : 'Add product' }}
        </h1>
        @if ($isEdit)
            <p class="mt-1 font-mono text-xs text-ink-subtle">{{ $product->sku }} · {{ $product->stock_status->label() }}</p>
        @endif
    </div>
@endsection

@section('content')
    @if ($isEdit && ($product->hasTemporaryPricing() || $product->hasEditorialDraft()))
        <div class="mb-4 space-y-2">
            @if ($product->hasTemporaryPricing())
                <div class="rounded-[var(--radius-panel)] border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-ink" role="status">
                    <p class="font-semibold text-ink">Temporary preview pricing</p>
                    <p class="mt-1 text-ink-muted">
                        This SKU uses shared placeholder pricing ({{ \App\Support\Money::format($product->price_cents) }}) and purchasing is disabled until real pricing and availability are approved.
                    </p>
                </div>
            @endif
            @if ($product->hasEditorialDraft())
                <div class="rounded-[var(--radius-panel)] border border-line bg-surface-muted/60 px-4 py-3 text-sm text-ink" role="status">
                    <p class="font-semibold text-ink">Editorial draft — client review required</p>
                    <p class="mt-1 text-ink-muted">
                        Expanded description, benefits, and label-derived specs are demonstration copy only. Confirm or replace before commercial publish.
                    </p>
                </div>
            @endif
        </div>
    @endif
    @include('admin.products._form')
@endsection
