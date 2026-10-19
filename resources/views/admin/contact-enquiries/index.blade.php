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
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <x-input name="q" type="search" placeholder="Search name, email, message…" :value="request('q')" class="max-w-sm" />
        <x-select name="type" class="max-w-xs">
            <option value="">All types</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </x-select>
        <x-select name="status" class="max-w-xs">
            <option value="">All statuses</option>
            <option value="unread" @selected(request('status') === 'unread')>Unread</option>
            <option value="read" @selected(request('status') === 'read')>Read</option>
        </x-select>
        <x-button type="submit" variant="outline">Filter</x-button>
    </form>

    <div class="overflow-hidden rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft">
        <table class="min-w-full divide-y divide-line text-left text-sm">
            <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">From</th>
                    <th class="px-4 py-3 font-semibold">Type</th>
                    <th class="px-4 py-3 font-semibold">Preview</th>
                    <th class="px-4 py-3 font-semibold">Received</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($enquiries as $enquiry)
                    <tr @class(['bg-brand-mist/40' => ! $enquiry->is_read])>
                        <td class="px-4 py-3">
                            <p class="font-medium text-ink">
                                @unless ($enquiry->is_read)
                                    <span class="mr-1 inline-block size-1.5 rounded-full bg-accent align-middle" aria-label="Unread"></span>
                                @endunless
                                {{ $enquiry->name }}
                            </p>
                            <p class="text-xs text-ink-subtle">{{ $enquiry->email }}</p>
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $enquiry->enquiryTypeLabel() }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ \Illuminate\Support\Str::limit($enquiry->message, 72) }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $enquiry->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-button href="{{ route('admin.contact-enquiries.show', $enquiry) }}" variant="ghost" size="sm">View</x-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-ink-muted">No enquiries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $enquiries->links() }}</div>
@endsection
