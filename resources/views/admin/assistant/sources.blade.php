@extends('layouts.admin')
@section('title', 'Assistant knowledge sources')
@section('breadcrumb', 'Assistant / Sources')
@section('page_header')
<div><h1 class="font-display text-2xl font-bold text-ink">Knowledge sources</h1><p class="mt-1 text-sm text-ink-muted">Review public content before the assistant uses it. Approval never overrides draft or publication restrictions.</p></div>
@endsection
@section('content')
@if(session('status'))<x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert>@endif
<x-card>
<div class="mb-6 flex flex-wrap gap-3">
<x-button href="{{ route('admin.assistant.edit') }}" variant="ghost">Back to Assistant</x-button>
@foreach(['product' => 'Products', 'post' => 'Articles', 'page' => 'Pages', 'category' => 'Categories'] as $key => $label)
<x-button href="{{ route('admin.assistant.sources', ['type' => $key]) }}" variant="{{ $type === $key ? 'primary' : 'ghost' }}">{{ $label }}</x-button>
@endforeach
</div>
<form method="GET" class="mb-6 flex flex-wrap items-end gap-3"><input type="hidden" name="type" value="{{ $type }}"><div><label for="source-search" class="mb-2 block text-sm font-medium">Search sources</label><input id="source-search" name="q" value="{{ request('q') }}" maxlength="120" class="rounded-lg border border-line bg-surface px-3 py-2 text-ink"></div><x-button type="submit">Search</x-button></form>
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b border-line"><th class="p-3">Source</th><th class="p-3">Approval</th><th class="p-3">Availability</th><th class="p-3">Action</th></tr></thead><tbody>
@forelse($sources as $source)
@php
 $approved = $approvals->has($source->id);
 $draft = $type === 'product' && $source->hasEditorialDraft();
 $published = $type === 'category' ? $source->is_active : ($type === 'product' ? $source->is_active && $source->catalog_visibility !== 'hidden' : $source->is_published) && $source->published_at && !$source->published_at->isFuture();
@endphp
<tr class="border-b border-line"><td class="p-3"><span class="font-semibold">{{ $source->name ?? $source->title }}</span><span class="block text-xs text-ink-muted">{{ $source->slug }}</span><details class="mt-2 max-w-lg"><summary class="cursor-pointer text-brand">Preview content</summary><p class="mt-2 text-ink-muted">{{ \Illuminate\Support\Str::limit(strip_tags($source->short_description ?? $source->body ?? $source->description ?? ''), 600) }}</p></details></td><td class="p-3">{{ $approved ? 'Approved' : 'Not approved' }}</td><td class="p-3">{{ $draft ? 'Blocked: editorial draft' : (!$published ? 'Blocked: not publicly available' : ($approved ? 'Available to assistant' : 'Ready for review')) }}</td><td class="p-3">
<form method="POST" action="{{ $approved ? route('admin.assistant.approvals.destroy') : route('admin.assistant.approvals.store') }}">@csrf @if($approved) @method('DELETE') @endif<input type="hidden" name="source_type" value="{{ $type }}"><input type="hidden" name="source_id" value="{{ $source->id }}"><x-button type="submit" variant="ghost" :disabled="!$approved && ($draft || !$published)">{{ $approved ? 'Withdraw approval' : 'Approve source' }}</x-button></form>
</td></tr>
@empty<tr><td colspan="4" class="p-6 text-ink-muted">No sources match your search.</td></tr>@endforelse
</tbody></table></div><div class="mt-6">{{ $sources->links() }}</div>
</x-card>
@endsection
