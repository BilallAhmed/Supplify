<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\MediaLibraryService;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaLibraryService $library,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', MediaAsset::class);

        if ($request->wantsJson() || $request->boolean('json')) {
            return $this->jsonIndex($request);
        }

        return view('admin.media.index');
    }

    public function show(MediaAsset $medium): JsonResponse
    {
        $this->authorize('view', $medium);

        return response()->json([
            'data' => $this->library->toArray($medium),
            'usages' => $this->library->findUsages($medium),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);

        $request->validate([
            'file' => ['required', 'file', 'max:2048'],
        ]);

        try {
            $asset = $this->library->upload($request->file('file'), $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable) {
            return response()->json(['message' => 'Upload failed. Please try again.'], 500);
        }

        return response()->json([
            'message' => 'Image uploaded.',
            'data' => $this->library->toArray($asset),
        ], 201);
    }

    public function update(Request $request, MediaAsset $medium): JsonResponse
    {
        $this->authorize('update', $medium);

        $data = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'alt' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $asset = $this->library->updateMeta($medium, $data);

        return response()->json([
            'message' => 'Media updated.',
            'data' => $this->library->toArray($asset),
        ]);
    }

    public function destroy(MediaAsset $medium): JsonResponse
    {
        $this->authorize('delete', $medium);

        $result = $this->library->delete($medium);

        return response()->json(
            ['message' => $result['message']],
            $result['ok'] ? 200 : 422
        );
    }

    public function resolve(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaAsset::class);

        $reference = MediaUrl::normalize($request->string('reference')->toString());
        $url = MediaUrl::toUrl($reference);
        $absolute = MediaUrl::absolutePath($reference);
        $size = $absolute ? @getimagesize($absolute) : false;

        $asset = null;
        if ($reference) {
            $asset = MediaAsset::query()
                ->where('path', $reference)
                ->orWhere('path', ltrim($reference, '/'))
                ->first();
        }

        return response()->json([
            'reference' => $reference,
            'url' => $url,
            'width' => is_array($size) ? $size[0] : null,
            'height' => is_array($size) ? $size[1] : null,
            'filename' => $reference ? basename($reference) : null,
            'asset' => $asset ? $this->library->toArray($asset) : null,
        ]);
    }

    public function reindex(): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);

        $result = $this->library->indexExistingAssets();

        return response()->json([
            'message' => 'Media library indexed.',
            'result' => $result,
        ]);
    }

    private function jsonIndex(Request $request): JsonResponse
    {
        $perPage = min(48, max(12, (int) $request->input('per_page', 24)));

        $query = MediaAsset::query()
            ->images()
            ->search($request->input('q'))
            ->when($request->filled('extension'), function ($q) use ($request) {
                $ext = strtolower((string) $request->input('extension'));
                $q->where('extension', $ext);
            })
            ->latest();

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (MediaAsset $asset) => $this->library->toArray($asset))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
