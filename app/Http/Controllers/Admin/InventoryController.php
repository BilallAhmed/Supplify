<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryUpdateRequest;
use App\Models\Product;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->when($request->string('filter')->toString() === 'low', fn ($q) => $q->where('stock_status', 'low_stock'))
            ->when($request->string('filter')->toString() === 'out', fn ($q) => $q->where('stock_status', 'out_of_stock'))
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.inventory.index', compact('products'));
    }

    public function update(InventoryUpdateRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->catalog->updateInventory(
            $product,
            (int) $request->validated('stock_quantity'),
            (int) $request->validated('low_stock_threshold')
        );

        return back()->with('flash_success', "Inventory updated for {$product->sku}.");
    }
}
