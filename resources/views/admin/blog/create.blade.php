@extends('layouts.admin')

@section('title', 'Add post')
@section('breadcrumb', 'Blog / Add')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Add post</h1>
        <p class="mt-1 text-sm text-ink-muted">Create a new insight for the storefront blog.</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.blog.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6 rounded-[var(--radius-panel)] border border-line bg-surface-elevated p-6 shadow-soft">
        @csrf
        @include('admin.blog._form')
        <div class="flex flex-wrap gap-3">
            <x-button type="submit" variant="primary">Create post</x-button>
            <x-button href="{{ route('admin.blog.index') }}" variant="ghost">Cancel</x-button>
        </div>
    </form>
@endsection
