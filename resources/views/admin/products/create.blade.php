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
    @include('admin.products._form')
@endsection
