@extends('layouts.admin')

@section('title', 'Edit post')
@section('breadcrumb', 'Blog / Edit')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Edit post</h1>
        <p class="mt-1 text-sm text-ink-muted">{{ $post->title }}</p>
    </div>
    @if ($post->is_published)
        <x-button href="{{ route('blog.show', $post->slug) }}" variant="outline" target="_blank">View on storefront</x-button>
    @endif
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.blog.update', $post) }}" enctype="multipart/form-data" class="admin-form">
        @csrf
        @method('PUT')
        <x-card>
            @include('admin.blog._form')
        </x-card>
        <div class="admin-form__actions">
            <x-button type="submit" variant="primary">Save changes</x-button>
            <x-button href="{{ route('admin.blog.index') }}" variant="ghost">Back</x-button>
        </div>
    </form>
@endsection
