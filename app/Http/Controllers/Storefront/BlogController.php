<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
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
        $filters = $request->only(['q', 'category', 'tag']);
        $hasFilters = $this->blog->listingHasActiveFilters($filters);
        $featuredCandidate = $hasFilters ? null : $this->blog->featuredForListing($filters);
        $featured = ($featuredCandidate && $request->integer('page', 1) <= 1)
            ? $featuredCandidate
            : null;

        return view('storefront.blog.index', [
            'posts' => $this->blog->paginatePublished($filters, $featuredCandidate?->id),
            'featured' => $featured,
            'totalMatching' => $this->blog->publishedMatchingCount($filters),
            'categories' => $this->blog->activeCategories(),
            'filters' => $filters,
        ]);
    }

    /**
     * Progressive “Load More” feed for the homepage education grid.
     */
    public function feed(Request $request): JsonResponse|RedirectResponse
    {
        $perPage = (int) $request->integer('per_page', 6);
        $page = max(1, (int) $request->integer('page', 1));

        $posts = $this->blog->paginateHomepageEducation($perPage, $page);

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()->to(
                route('home', ['education_page' => $posts->currentPage()]).'#education'
            );
        }

        $html = view('storefront.home.partials.education-cards', [
            'posts' => $posts,
        ])->render();

        return response()->json([
            'html' => $html,
            'has_more' => $posts->hasMorePages(),
            'next_page' => $posts->hasMorePages() ? $posts->currentPage() + 1 : null,
            'count' => $posts->count(),
            'message' => $posts->count() === 1
                ? '1 more article loaded.'
                : $posts->count().' more articles loaded.',
        ]);
    }

    public function show(string $post): View
    {
        $item = $this->blog->findPublished($post);

        return view('storefront.blog.show', [
            'post' => $item,
            'related' => $this->blog->relatedPosts($item),
        ]);
    }
}
