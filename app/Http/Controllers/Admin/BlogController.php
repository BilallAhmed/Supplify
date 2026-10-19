<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\BlogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(
        private readonly BlogService $blog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::query()
            ->with(['category:id,name', 'author:id,name'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($inner) => $inner
                    ->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('excerpt', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.blog.index', compact('posts'));
    }

    public function create(): View
    {
        $this->authorize('create', Post::class);

        return view('admin.blog.create', [
            'post' => new Post(['is_published' => false]),
            'categories' => PostCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'tagsValue' => '',
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $post = $this->blog->save(
            new Post,
            $request->validated(),
            null,
            $request->user()?->id
        );

        return redirect()
            ->route('admin.blog.edit', $post)
            ->with('flash_success', 'Post created.');
    }

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);
        $post->load(['tags', 'category']);

        return view('admin.blog.edit', [
            'post' => $post,
            'categories' => PostCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'tagsValue' => $post->tags->pluck('name')->implode(', '),
        ]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $this->blog->save(
            $post,
            $request->validated(),
            null,
            $request->user()?->id
        );

        return redirect()
            ->route('admin.blog.edit', $post)
            ->with('flash_success', 'Post updated.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);
        $this->blog->delete($post);

        return redirect()
            ->route('admin.blog.index')
            ->with('flash_success', 'Post deleted.');
    }
}
