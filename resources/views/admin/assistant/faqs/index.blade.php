@extends('layouts.admin')

@section('title', 'Assistant FAQs')
@section('breadcrumb', 'Assistant / FAQs')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Assistant FAQs</h1>
        <p class="mt-1 text-sm text-ink-muted">Reviewed Q&amp;A entries used by the website-only shopping assistant.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <x-button href="{{ route('admin.assistant.edit') }}" variant="ghost">Back to Assistant</x-button>
        <x-button href="{{ route('admin.assistant.faqs.create') }}" variant="primary">Add FAQ</x-button>
    </div>
@endsection

@section('content')
    @if (session('flash_success'))
        <x-alert variant="success" class="mb-6">{{ session('flash_success') }}</x-alert>
    @endif

    <div class="admin-table-panel">
        <form method="GET" class="admin-toolbar">
            <div class="admin-toolbar__search">
                <x-input name="q" type="search" placeholder="Search questions…" :value="request('q')" aria-label="Search FAQs" />
            </div>
            <div class="admin-toolbar__control">
                <x-select name="status" aria-label="Status">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </x-select>
            </div>
            <div class="admin-toolbar__actions">
                @if (request()->anyFilled(['q', 'status']))
                    <x-button href="{{ route('admin.assistant.faqs.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
                <x-button type="submit" variant="primary" size="sm">Search</x-button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Intent</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faqs as $faq)
                        <tr>
                            <td>
                                <p class="font-medium text-ink">{{ $faq->question }}</p>
                                <p class="mt-0.5 line-clamp-1 text-xs text-ink-subtle">{{ \Illuminate\Support\Str::limit($faq->answer, 80) }}</p>
                            </td>
                            <td class="text-ink-muted">{{ $faq->intent?->label() ?? '—' }}</td>
                            <td class="text-ink-muted">{{ $faq->sort_order }}</td>
                            <td>
                                <x-badge :variant="$faq->is_active ? 'success' : 'neutral'">
                                    {{ $faq->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </td>
                            <td>
                                <div class="admin-row-actions">
                                    <x-button href="{{ route('admin.assistant.faqs.edit', $faq) }}" variant="ghost" size="sm">Edit</x-button>
                                    <form method="POST" action="{{ route('admin.assistant.faqs.destroy', $faq) }}" onsubmit="return confirm('Delete this FAQ?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-data-table__empty">No FAQs match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($faqs->hasPages())
            <div class="admin-table-panel__footer">{{ $faqs->links() }}</div>
        @endif
    </div>
@endsection
