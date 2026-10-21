@extends('layouts.admin')

@section('title', 'Pages')
@section('breadcrumb', 'Pages')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Pages</h1>
        <p class="mt-1 text-sm text-ink-muted">About, FAQ, Shipping, Returns, Privacy, Terms, and similar content.</p>
    </div>
    <x-button href="{{ route('admin.pages.create') }}" variant="primary">Add page</x-button>
@endsection

@section('content')
    <div class="admin-table-panel">
        <form method="GET" class="admin-toolbar">
            <div class="admin-toolbar__search">
                <x-input name="q" type="search" placeholder="Search pages…" :value="request('q')" aria-label="Search pages" />
            </div>
            <div class="admin-toolbar__actions">
                @if (request()->filled('q'))
                    <x-button href="{{ route('admin.pages.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
                <x-button type="submit" variant="primary" size="sm">Search</x-button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td>
                                <p class="font-medium text-ink">{{ $page->title }}</p>
                                <p class="text-xs text-ink-subtle">/pages/{{ $page->slug }}</p>
                            </td>
                            <td>
                                <x-badge :variant="$page->is_published ? 'success' : 'neutral'">
                                    {{ $page->is_published ? 'Published' : 'Draft' }}
                                </x-badge>
                            </td>
                            <td class="text-ink-muted">{{ $page->updated_at?->diffForHumans() }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    @if ($page->is_published)
                                        <x-button href="{{ route('pages.show', $page->slug) }}" variant="ghost" size="sm" target="_blank">View</x-button>
                                    @endif
                                    <x-button href="{{ route('admin.pages.edit', $page) }}" variant="ghost" size="sm">Edit</x-button>
                                    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="admin-data-table__empty">No pages yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pages->hasPages())
            <div class="admin-table-panel__pager">{{ $pages->links() }}</div>
        @endif
    </div>
@endsection
