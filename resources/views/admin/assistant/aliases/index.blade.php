@extends('layouts.admin')

@section('title', 'Assistant aliases')
@section('breadcrumb', 'Assistant / Aliases')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Assistant aliases</h1>
        <p class="mt-1 text-sm text-ink-muted">Reviewed synonym phrases that improve product and intent matching.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <x-button href="{{ route('admin.assistant.edit') }}" variant="ghost">Back to Assistant</x-button>
        <x-button href="{{ route('admin.assistant.aliases.create') }}" variant="primary">Add alias</x-button>
    </div>
@endsection

@section('content')
    @if (session('flash_success'))
        <x-alert variant="success" class="mb-6">{{ session('flash_success') }}</x-alert>
    @endif

    <div class="admin-table-panel">
        <form method="GET" class="admin-toolbar">
            <div class="admin-toolbar__search">
                <x-input name="q" type="search" placeholder="Search phrases…" :value="request('q')" aria-label="Search aliases" />
            </div>
            <div class="admin-toolbar__control">
                <x-select name="locale" aria-label="Locale">
                    <option value="">All locales</option>
                    <option value="en" @selected(request('locale') === 'en')>English</option>
                    <option value="roman_ur" @selected(request('locale') === 'roman_ur')>Roman Urdu</option>
                    <option value="ur" @selected(request('locale') === 'ur')>Urdu</option>
                </x-select>
            </div>
            <div class="admin-toolbar__control">
                <x-select name="status" aria-label="Status">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </x-select>
            </div>
            <div class="admin-toolbar__actions">
                @if (request()->anyFilled(['q', 'locale', 'status']))
                    <x-button href="{{ route('admin.assistant.aliases.index') }}" variant="ghost" size="sm">Clear</x-button>
                @endif
                <x-button type="submit" variant="primary" size="sm">Search</x-button>
            </div>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Phrase</th>
                        <th>Locale</th>
                        <th>Intent</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aliases as $alias)
                        <tr>
                            <td>
                                <p class="font-medium text-ink">{{ $alias->phrase }}</p>
                                <p class="text-xs text-ink-subtle">{{ $alias->normalized_phrase }}</p>
                            </td>
                            <td class="text-ink-muted">{{ $alias->locale }}</td>
                            <td class="text-ink-muted">{{ $alias->maps_to_intent?->label() ?? '—' }}</td>
                            <td class="text-ink-muted">
                                @if ($alias->target_type)
                                    {{ $alias->target_type->value }} #{{ $alias->target_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <x-badge :variant="$alias->is_active ? 'success' : 'neutral'">
                                    {{ $alias->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </td>
                            <td>
                                <div class="admin-row-actions">
                                    <x-button href="{{ route('admin.assistant.aliases.edit', $alias) }}" variant="ghost" size="sm">Edit</x-button>
                                    <form method="POST" action="{{ route('admin.assistant.aliases.destroy', $alias) }}" onsubmit="return confirm('Delete this alias?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-data-table__empty">No aliases match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($aliases->hasPages())
            <div class="admin-table-panel__footer">{{ $aliases->links() }}</div>
        @endif
    </div>
@endsection
