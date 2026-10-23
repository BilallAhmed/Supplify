@extends('layouts.admin')

@section('title', 'Supplify Assistant')
@section('breadcrumb', 'Assistant')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Supplify Assistant</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Website-powered shopping help. No external AI API. Public widget appears only when config and runtime enable are both on.
        </p>
    </div>
@endsection

@section('content')
    @if (session('status'))
        <x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if(($syncStatus['documents_active'] ?? 0) === 0)
        <x-alert variant="warning" class="mb-6">Knowledge is empty. Review and approve sources, add FAQs and aliases, then rebuild before sharing the assistant with customers.</x-alert>
    @endif
    <div class="space-y-6">
        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Knowledge controls</h2>
                <p class="admin-form__section-desc">
                    Maintain reviewed FAQs and aliases, then rebuild the searchable index when needed.
                </p>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-button href="{{ route('admin.assistant.sources') }}" variant="primary">Review sources</x-button>
                <x-button href="{{ route('admin.assistant.faqs.index') }}" variant="primary">
                    FAQs ({{ $faqCount }})
                </x-button>
                <x-button href="{{ route('admin.assistant.aliases.index') }}" variant="primary">
                    Aliases ({{ $aliasCount }})
                </x-button>
            </div>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Operating status</h2>
                <p class="admin-form__section-desc">
                    Config flag <code class="text-xs">ASSISTANT_ENABLED</code>
                    is {{ $configEnabled ? 'true' : 'false' }}.
                    Runtime enable is {{ $runtimeEnabled ? 'on' : 'off' }}.
                    Effective public availability: <strong>{{ $effectivelyEnabled ? 'on' : 'off' }}</strong>.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.assistant.update') }}" class="admin-form mt-4">
                @csrf
                @method('PUT')
                <label class="flex items-start gap-3 text-sm text-ink">
                    <input type="hidden" name="enabled" value="0">
                    <input
                        type="checkbox"
                        name="enabled"
                        value="1"
                        class="mt-1"
                        @checked(old('enabled', $runtimeEnabled))
                    >
                    <span>
                        <span class="font-medium">Enable runtime assistant</span>
                        <span class="mt-1 block text-ink-muted">
                            Still requires <code class="text-xs">ASSISTANT_ENABLED=true</code> in the environment before the storefront widget can appear.
                        </span>
                    </span>
                </label>
                <div class="mt-4">
                    <x-button type="submit" variant="primary">Save assistant settings</x-button>
                </div>
            </form>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Knowledge sync</h2>
                <p class="admin-form__section-desc">
                    Indexed after commit via queue jobs. Reconcile hourly with the scheduler.
                    CLI: <code class="text-xs">php artisan assistant:rebuild-knowledge [--sync]</code>
                </p>
            </div>
            <ul class="mt-3 grid gap-2 text-sm text-ink-muted sm:grid-cols-2">
                <li><span class="font-medium text-ink">Active documents</span>: {{ $syncStatus['documents_active'] ?? 0 }}</li>
                <li><span class="font-medium text-ink">Removed documents</span>: {{ $syncStatus['documents_removed'] ?? 0 }}</li>
                <li><span class="font-medium text-ink">Pending assistant jobs</span>: {{ $syncStatus['pending_jobs'] ?? 0 }}</li>
                <li><span class="font-medium text-ink">Failed assistant jobs</span>: {{ $syncStatus['failed_jobs'] ?? 0 }}</li>
                <li><span class="font-medium text-ink">Oldest pending job</span>: {{ !empty($syncStatus['oldest_pending_at']) ? \Carbon\Carbon::createFromTimestamp($syncStatus['oldest_pending_at'])->diffForHumans() : 'none' }}</li>
                <li><span class="font-medium text-ink">Last reconciliation</span>: {{ $syncStatus['last_reconciliation']['finished_at'] ?? 'never' }}</li>
                <li><span class="font-medium text-ink">Chunks</span>: {{ $syncStatus['chunks'] ?? 0 }}</li>
                <li><span class="font-medium text-ink">Last successful sync</span>: {{ $syncStatus['last_successful_sync']['finished_at'] ?? 'never' }}</li>
                <li class="sm:col-span-2"><span class="font-medium text-ink">Last failure</span>: {{ $syncStatus['last_failure']['message'] ?? 'none' }}</li>
                <li class="sm:col-span-2"><span class="font-medium text-ink">Search driver</span>: {{ $searchDriver ?? 'portable_token' }} (no vector DB; FULLTEXT optional on MySQL)</li>
            </ul>
            <div class="mt-4 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('admin.assistant.rebuild') }}">
                    @csrf
                    <x-button type="submit" variant="primary">Queue knowledge rebuild</x-button>
                </form>
                <form method="POST" action="{{ route('admin.assistant.rebuild') }}" onsubmit="return confirm('Rebuild the full knowledge index inline? This may take a moment.')">
                    @csrf
                    <input type="hidden" name="sync" value="1">
                    <x-button type="submit" variant="ghost">Rebuild now (sync)</x-button>
                </form>
            </div>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Limits</h2>
                <p class="admin-form__section-desc">Bounded inputs and result sizes for later conversation layers.</p>
            </div>
            <ul class="mt-3 grid gap-2 text-sm text-ink-muted sm:grid-cols-2">
                @foreach ($limits as $key => $value)
                    <li><span class="font-medium text-ink">{{ $key }}</span>: {{ $value }}</li>
                @endforeach
            </ul>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Readable fields</h2>
                <p class="admin-form__section-desc">
                    Public visibility alone is not enough. Sources also need explicit assistant approval (except active FAQs).
                </p>
            </div>
            <div class="mt-3 space-y-4 text-sm">
                @foreach ($readableFields as $type => $fields)
                    <div>
                        <p class="font-medium text-ink">{{ $type }}</p>
                        <p class="text-ink-muted">{{ implode(', ', $fields) }}</p>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card class="admin-form__section">
            <div class="admin-form__section-head">
                <h2 class="admin-form__section-title">Prohibited</h2>
                <p class="admin-form__section-desc">Never exposed through assistant answers.</p>
            </div>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-ink-muted">
                @foreach ($prohibitedFields as $field)
                    <li>{{ $field }}</li>
                @endforeach
            </ul>
        </x-card>
    </div>
@endsection
