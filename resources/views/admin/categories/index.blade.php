@extends('layouts.admin')

@section('title', 'Categories')
@section('breadcrumb', 'Categories')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Categories</h1>
        <p class="mt-1 text-sm text-ink-muted">Organize products for shop navigation and shop-by-goal discovery.</p>
    </div>
    <x-button href="{{ route('admin.categories.create') }}" variant="primary">Add category</x-button>
@endsection

@section('content')
    <div class="admin-table-panel">
        <form method="GET" class="admin-toolbar">
            <div class="admin-toolbar__search">
                <x-input name="q" type="search" placeholder="Search categories…" :value="request('q')" aria-label="Search categories" />
            </div>
            <div class="admin-toolbar__actions">
                @if (request()->filled('q'))
                    <x-button href="{{ route('admin.categories.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
                <x-button type="submit" variant="primary" size="sm">Search</x-button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Parent</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Sort</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <p class="font-medium text-ink">{{ $category->name }}</p>
                                <p class="text-xs text-ink-subtle">{{ $category->slug }}</p>
                            </td>
                            <td class="text-ink-muted">{{ $category->parent?->name ?? '—' }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>
                                <x-badge :variant="$category->is_active ? 'success' : 'neutral'">
                                    {{ $category->is_active ? 'Active' : 'Hidden' }}
                                </x-badge>
                            </td>
                            <td>{{ $category->sort_order }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <x-button href="{{ route('admin.categories.edit', $category) }}" variant="ghost" size="sm">Edit</x-button>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-data-table__empty">No categories yet. Create your first category.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="admin-table-panel__pager">{{ $categories->links() }}</div>
        @endif
    </div>
@endsection
