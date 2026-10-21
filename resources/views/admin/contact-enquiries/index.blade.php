@extends('layouts.admin')

@section('title', 'Contact enquiries')
@section('breadcrumb', 'Contact enquiries')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Contact enquiries</h1>
        <p class="mt-1 text-sm text-ink-muted">Messages submitted from the Contact page.</p>
    </div>
@endsection

@section('content')
    <div class="admin-table-panel">
        <form method="GET" class="admin-toolbar">
            <div class="admin-toolbar__search">
                <x-input name="q" type="search" placeholder="Search name, email, message…" :value="request('q')" aria-label="Search enquiries" />
            </div>
            <div class="admin-toolbar__control">
                <x-select name="type" aria-label="Enquiry type">
                    <option value="">All types</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="admin-toolbar__control">
                <x-select name="status" aria-label="Read status">
                    <option value="">All statuses</option>
                    <option value="unread" @selected(request('status') === 'unread')>Unread</option>
                    <option value="read" @selected(request('status') === 'read')>Read</option>
                </x-select>
            </div>
            <div class="admin-toolbar__actions">
                @if (request()->filled('q') || request()->filled('type') || request()->filled('status'))
                    <x-button href="{{ route('admin.contact-enquiries.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
                <x-button type="submit" variant="primary" size="sm">Apply filters</x-button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Type</th>
                        <th>Preview</th>
                        <th>Received</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enquiries as $enquiry)
                        <tr @class(['bg-brand-mist/40' => ! $enquiry->is_read])>
                            <td>
                                <p class="font-medium text-ink">
                                    @unless ($enquiry->is_read)
                                        <span class="mr-1 inline-block size-1.5 rounded-full bg-accent align-middle" aria-label="Unread"></span>
                                    @endunless
                                    {{ $enquiry->name }}
                                </p>
                                <p class="text-xs text-ink-subtle">{{ $enquiry->email }}</p>
                            </td>
                            <td class="text-ink-muted">{{ $enquiry->enquiryTypeLabel() }}</td>
                            <td class="text-ink-muted">{{ \Illuminate\Support\Str::limit($enquiry->message, 72) }}</td>
                            <td class="text-ink-muted whitespace-nowrap">
                                {{ $enquiry->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                            </td>
                            <td>
                                <div class="admin-row-actions">
                                    <x-button href="{{ route('admin.contact-enquiries.show', $enquiry) }}" variant="outline" size="sm">View</x-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-data-table__empty">No enquiries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($enquiries->hasPages())
            <div class="admin-table-panel__pager">{{ $enquiries->links() }}</div>
        @endif
    </div>
@endsection
