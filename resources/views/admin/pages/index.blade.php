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
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <x-input name="q" type="search" placeholder="Search pages…" :value="request('q')" class="max-w-sm" />
        <x-button type="submit" variant="outline">Search</x-button>
    </form>

    <div class="overflow-hidden rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft">
        <table class="min-w-full divide-y divide-line text-left text-sm">
            <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Title</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Updated</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($pages as $page)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-ink">{{ $page->title }}</p>
                            <p class="text-xs text-ink-subtle">/pages/{{ $page->slug }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$page->is_published ? 'success' : 'neutral'">
                                {{ $page->is_published ? 'Published' : 'Draft' }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $page->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
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
                        <td colspan="4" class="px-4 py-10 text-center text-ink-muted">No pages yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $pages->links() }}</div>
@endsection
