@extends('layouts.admin')

@section('title', 'Add coupon')
@section('breadcrumb', 'Coupons / New')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Add coupon</h1>
    </div>
@endsection

@section('content')
    @include('admin.coupons._form')
@endsection
