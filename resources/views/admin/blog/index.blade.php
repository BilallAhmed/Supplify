@extends('layouts.admin')

@section('title', 'Blog')
@section('breadcrumb', 'Blog')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Blog</h1>
        <p class="mt-1 text-sm text-ink-muted">Publish insights with featured images, categories, tags, and SEO.</p>
    </div>
    <x-button href="{{ route('admin.blog.create') }}" variant="primary">Add post</x-button>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-danger p-4 text-sm text-danger" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    <div class="admin-table-panel">
        <form method="GET" class="admin-toolbar">
            <div class="admin-toolbar__search">
                <x-input name="q" type="search" placeholder="Search posts…" :value="request('q')" aria-label="Search posts" />
            </div>
<div class="admin-toolbar__control"><x-select name="status" aria-label="All post statuses"><option value="">All post statuses</option><option value="draft" @selected(request('status') === 'draft')>Draft</option><option value="published" @selected(request('status') === 'published')>Published</option><option value="scheduled" @selected(request('status') === 'scheduled')>Scheduled</option></x-select></div>
<div class="admin-toolbar__control"><x-select name="category" aria-label="Post category"><option value="">All categories</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach</x-select></div><div class="admin-toolbar__actions">
                @if (request()->anyFilled(['q', 'status', 'category']))
                    <x-button href="{{ route('admin.blog.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
                <x-button type="submit" variant="primary" size="sm">Search</x-button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td>
                                <p class="font-medium text-ink">{{ $post->title }}</p>
                                <p class="text-xs text-ink-subtle">/blog/{{ $post->slug }}</p>
                            </td>
                            <td class="text-ink-muted">{{ $post->category?->name ?: '—' }}</td>
                            <td>
                                <x-badge :variant="$post->is_published ? 'success' : 'neutral'">
                                    {{ ! $post->is_published || ! $post->published_at ? 'Draft' : ($post->published_at->isFuture() ? 'Scheduled' : 'Published') }}
                                </x-badge>
                            </td>
                            <td class="text-ink-muted">{{ $post->updated_at?->diffForHumans() }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    @if ($post->is_published && $post->published_at && ! $post->published_at->isFuture())
                                        <x-button href="{{ route('blog.show', $post->slug) }}" variant="ghost" size="sm" target="_blank">View</x-button>
                                    @endif
                                    <x-button href="{{ route('admin.blog.edit', $post) }}" variant="ghost" size="sm">Edit</x-button>
                                    <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" onsubmit="return confirm('Delete this post?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-data-table__empty">No posts match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($posts->hasPages())
            <div class="admin-table-panel__pager">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
