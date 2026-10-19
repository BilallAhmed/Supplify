@extends('layouts.admin')

@section('title', 'Enquiry from '.$enquiry->name)
@section('breadcrumb', 'Contact enquiries / '.$enquiry->name)

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">{{ $enquiry->name }}</h1>
        <p class="mt-1 text-sm text-ink-muted">
            {{ $enquiry->enquiryTypeLabel() }}
            · Received {{ $enquiry->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
        </p>
    </div>
    <x-button href="{{ route('admin.contact-enquiries.index') }}" variant="outline">Back to enquiries</x-button>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-card class="space-y-4 text-sm">
            <h2 class="font-display text-lg font-bold text-ink">Contact</h2>
            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">Name</dt>
                    <dd class="mt-0.5 text-ink">{{ $enquiry->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">Email</dt>
                    <dd class="mt-0.5">
                        <a class="font-medium text-brand hover:text-brand-hover" href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">Phone</dt>
                    <dd class="mt-0.5 text-ink">{{ $enquiry->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold tracking-wide text-ink-subtle uppercase">Enquiry type</dt>
                    <dd class="mt-0.5 text-ink">{{ $enquiry->enquiryTypeLabel() }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card class="space-y-3">
            <h2 class="font-display text-lg font-bold text-ink">Message</h2>
            <div class="whitespace-pre-wrap text-sm leading-relaxed text-ink-muted">{{ $enquiry->message }}</div>
        </x-card>

        <x-card class="space-y-2 text-sm text-ink-muted">
            <h2 class="font-display text-lg font-bold text-ink">Meta</h2>
            <p>IP: {{ $enquiry->ip_address ?: '—' }}</p>
            <p class="break-all">User agent: {{ $enquiry->user_agent ?: '—' }}</p>
            <p>Read: {{ $enquiry->is_read ? ($enquiry->read_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? 'Yes') : 'Unread' }}</p>
        </x-card>

        <form method="POST" action="{{ route('admin.contact-enquiries.destroy', $enquiry) }}" onsubmit="return confirm('Delete this enquiry?')">
            @csrf
            @method('DELETE')
            <x-button type="submit" variant="danger">Delete enquiry</x-button>
        </form>
    </div>
@endsection
