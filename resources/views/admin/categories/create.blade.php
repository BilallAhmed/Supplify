@php
    $isEdit = $category->exists;
@endphp

@extends('layouts.admin')

@section('title', $isEdit ? 'Edit category' : 'Add category')
@section('breadcrumb', $isEdit ? 'Categories / Edit' : 'Categories / New')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
            {{ $isEdit ? 'Edit category' : 'Add category' }}
        </h1>
    </div>
@endsection

@section('content')
    @include('admin.categories._form')
@endsection
