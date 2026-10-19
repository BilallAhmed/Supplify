@extends('layouts.admin')

@section('title', 'Blog')
@section('breadcrumb', 'Blog')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Blog</h1>
        <p class="mt-1 text-sm text-ink-muted">Publish insights with featured images, categories, tags, and SEO.</p>
    </div>
    <x-button href="{{ route('admin.blog.create') }}" variant="primary">Add post</x-button>
@endsection

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <x-input name="q" type="search" placeholder="Search posts…" :value="request('q')" class="max-w-sm" />
        <x-button type="submit" variant="outline">Search</x-button>
    </form>

    <div class="overflow-hidden rounded-[var(--radius-panel)] border border-line bg-surface-elevated shadow-soft">
        <table class="min-w-full divide-y divide-line text-left text-sm">
            <thead class="bg-surface-muted/70 text-xs tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Title</th>
                    <th class="px-4 py-3 font-semibold">Category</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Updated</th>
                    <th class="px-4 py-3 font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($posts as $post)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-ink">{{ $post->title }}</p>
                            <p class="text-xs text-ink-subtle">/blog/{{ $post->slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $post->category?->name ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$post->is_published ? 'success' : 'neutral'">
                                {{ $post->is_published ? 'Published' : 'Draft' }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $post->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                @if ($post->is_published)
                                    <x-button href="{{ route('blog.show', $post->slug) }}" variant="ghost" size="sm" target="_blank">View</x-button>
                                @endif
                                <x-button href="{{ route('admin.blog.edit', $post) }}" variant="ghost" size="sm">Edit</x-button>
                                <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" onsubmit="return confirm('Delete this post?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-ink-muted">No posts yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $posts->links() }}</div>
@endsection
