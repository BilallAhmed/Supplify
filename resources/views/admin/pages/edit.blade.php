@extends('layouts.admin')

@section('title', 'Edit '.$page->title)
@section('breadcrumb', 'Pages / Edit')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink">Edit page</h1>
        <p class="mt-1 text-sm text-ink-muted">{{ $page->title }}</p>
    </div>
@endsection

@section('content')
    <form
        method="POST"
        action="{{ route('admin.pages.update', $page) }}"
        class="admin-page-editor"
        id="page-editor-form"
    >
        @csrf
        @method('PUT')

        <div class="admin-page-editor__main">
            @include('admin.pages._form_main')
        </div>

        <aside class="admin-page-editor__aside" aria-label="Page settings">
            @include('admin.pages._form_sidebar')
        </aside>

        <div class="admin-page-editor__mobile-bar" aria-hidden="false">
            <x-button type="submit" variant="primary" class="min-w-[10rem] justify-center">Save changes</x-button>
            <x-button href="{{ route('admin.pages.index') }}" variant="ghost" size="sm">Back</x-button>
        </div>
    </form>
@endsection
