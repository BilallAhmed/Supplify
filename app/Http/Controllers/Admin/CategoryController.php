<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\CatalogService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly MediaService $media,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->with('parent')
            ->withCount('products')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('slug', 'like', $term));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create', [
            'category' => new Category(['is_active' => true, 'sort_order' => 0]),
            'parents' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $data = $request->validated();
        $data['slug'] = $this->catalog->uniqueSlug($data['slug'] ?? $data['name'], Category::class);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if (array_key_exists('icon_path', $data)) {
            $data['icon_path'] = \App\Support\MediaUrl::normalize($data['icon_path'] ?? null);
        }

        Category::query()->create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('flash_success', 'Category created.');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
        ]);

        $name = trim($data['name']);

        $existing = Category::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        if ($existing) {
            return response()->json([
                'category' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                ],
                'created' => false,
                'message' => 'Category already exists.',
            ]);
        }

        $category = Category::query()->create([
            'name' => $name,
            'slug' => $this->catalog->uniqueSlug($name, Category::class),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'created' => true,
            'message' => 'Category created.',
        ], 201);
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', [
            'category' => $category,
            'parents' => Category::query()
                ->where('id', '!=', $category->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $data = $request->validated();
        $data['slug'] = $this->catalog->uniqueSlug($data['slug'] ?? $data['name'], Category::class, $category->id);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if (array_key_exists('icon_path', $data)) {
            $data['icon_path'] = \App\Support\MediaUrl::normalize($data['icon_path'] ?? null);
        }

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('flash_success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $this->media->deletePublicFile($category->icon_path);
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('flash_success', 'Category deleted.');
    }
}
