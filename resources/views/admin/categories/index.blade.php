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
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <x-input name="q" type="search" placeholder="Search categories…" :value="request('q')" class="max-w-sm" />
        <x-button type="submit" variant="outline">Search</x-button>
    </form>

    <div class="overflow-hidden rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft">
        <table class="min-w-full divide-y divide-line text-left text-sm">
            <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Name</th>
                    <th class="px-4 py-3 font-semibold">Parent</th>
                    <th class="px-4 py-3 font-semibold">Products</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Sort</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-ink">{{ $category->name }}</p>
                            <p class="text-xs text-ink-subtle">{{ $category->slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $category->parent?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $category->products_count }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$category->is_active ? 'success' : 'neutral'">
                                {{ $category->is_active ? 'Active' : 'Hidden' }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-3">{{ $category->sort_order }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
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
                        <td colspan="6" class="px-4 py-10 text-center text-ink-muted">No categories yet. Create your first category.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>
@endsection
