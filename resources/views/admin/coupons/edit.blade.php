@extends('layouts.admin')

@section('title', 'Edit coupon')
@section('breadcrumb', 'Coupons / Edit')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Edit coupon</h1>
        <p class="mt-1 text-sm text-ink-muted">Used {{ $coupon->used_count }} time{{ $coupon->used_count === 1 ? '' : 's' }}.</p>
    </div>
@endsection

@section('content')
    @include('admin.coupons._form')
@endsection
