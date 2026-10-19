@extends('layouts.admin')

@section('title', 'Add page')
@section('breadcrumb', 'Pages / New')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink">Add page</h1>
        <p class="mt-1 text-sm text-ink-muted">Create a new storefront page.</p>
    </div>
@endsection

@section('content')
    <form
        method="POST"
        action="{{ route('admin.pages.store') }}"
        class="admin-page-editor"
        id="page-editor-form"
    >
        @csrf

        <div class="admin-page-editor__main">
            @include('admin.pages._form_main')
        </div>

        <aside class="admin-page-editor__aside" aria-label="Page settings">
            @include('admin.pages._form_sidebar')
        </aside>

        <div class="admin-page-editor__mobile-bar">
            <x-button type="submit" variant="primary" class="min-w-[10rem] justify-center">Create page</x-button>
            <x-button href="{{ route('admin.pages.index') }}" variant="ghost" size="sm">Cancel</x-button>
        </div>
    </form>
@endsection
