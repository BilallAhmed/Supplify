<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use App\Services\ContentService;
use App\Support\AboutPageContent;
use App\Support\ContactPageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Page::class);

        $pages = Page::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($inner) => $inner->where('title', 'like', $term)->orWhere('slug', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.index', compact('pages'));
    }

    public function create(): View
    {
        $this->authorize('create', Page::class);

        return view('admin.pages.create', [
            'page' => new Page(['is_published' => false]),
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $this->authorize('create', Page::class);

        $page = $this->persist(new Page, $request);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('flash_success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        $this->authorize('update', $page);

        return view('admin.pages.edit', [
            'page' => $page,
            'about' => $page->isAboutPage() ? AboutPageContent::resolve($page) : null,
            'contact' => $page->isContactPage() ? ContactPageContent::resolve($page) : null,
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $this->authorize('update', $page);

        $this->persist($page, $request);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('flash_success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('flash_success', 'Page deleted.');
    }

    private function persist(Page $page, PageRequest $request): Page
    {
        $data = $request->validated();
        $slug = filled($data['slug'] ?? null)
            ? $this->content->uniquePageSlug($data['slug'], $page->id)
            : $this->content->uniquePageSlug($data['title'], $page->id);

        $page->fill([
            'title' => $data['title'],
            'slug' => $slug,
            'body' => $data['body'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'is_published' => (bool) ($data['is_published'] ?? false),
        ]);

        if ($slug === 'about' || $page->isAboutPage()) {
            $page->sections = $this->resolveAboutSections($page, $data);
        }

        if ($slug === 'contact' || $page->isContactPage()) {
            $page->sections = $this->resolveContactSections($page, $data);
        }

        if ($page->is_published && ! $page->published_at) {
            $page->published_at = now();
        }

        $page->save();

        return $page;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveAboutSections(Page $page, array $data): array
    {
        $base = AboutPageContent::resolve($page);

        if (! empty($data['about']) && is_array($data['about'])) {
            return AboutPageContent::normalizeFlags(array_replace_recursive($base, $data['about']));
        }

        if (filled($data['sections_json'] ?? null)) {
            $decoded = json_decode((string) $data['sections_json'], true);
            if (is_array($decoded)) {
                return AboutPageContent::normalizeFlags(array_replace_recursive($base, $decoded));
            }
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveContactSections(Page $page, array $data): array
    {
        $base = ContactPageContent::resolve($page);

        if (! empty($data['contact']) && is_array($data['contact'])) {
            return ContactPageContent::normalize(array_replace_recursive($base, $data['contact']));
        }

        if (filled($data['sections_json'] ?? null)) {
            $decoded = json_decode((string) $data['sections_json'], true);
            if (is_array($decoded)) {
                return ContactPageContent::normalize(array_replace_recursive($base, $decoded));
            }
        }

        return $base;
    }
}
