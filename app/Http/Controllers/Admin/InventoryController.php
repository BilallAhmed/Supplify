<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryUpdateRequest;
use App\Models\Product;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()->with('variations')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhereHas('variations', fn ($v) => $v->where('sku', 'like', $term)));
            })
            ->when($request->string('filter')->toString() === 'low', fn ($q) => $q->where('stock_status', 'low_stock'))
            ->when($request->string('filter')->toString() === 'out', fn ($q) => $q->where('stock_status', 'out_of_stock'))
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.inventory.index', compact('products'));
    }

    public function updateVariation(InventoryUpdateRequest $request, Product $product, int $variation): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($product->isVariable(), 404);
        DB::transaction(function () use ($request, $product, $variation) {
            $parent = Product::query()->lockForUpdate()->findOrFail($product->id);
            $item = $parent->variations()->lockForUpdate()->findOrFail($variation);
            $item->manage_stock = $request->boolean('manage_stock');
            $item->stock_quantity = (int) $request->validated('stock_quantity');
            $item->low_stock_threshold = (int) $request->validated('low_stock_threshold');
            $item->syncStockStatus();
            $item->save();
            $parent->syncStockStatus();
            $parent->save();
        });

        return back()->with('flash_success', 'Variation inventory updated.');
    }

    public function update(InventoryUpdateRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_if($product->isVariable(), 422, 'Update stock on individual variations.');
        $this->catalog->updateInventory(
            $product,
            (int) $request->validated('stock_quantity'),
            (int) $request->validated('low_stock_threshold')
        );

        return back()->with('flash_success', "Inventory updated for {$product->sku}.");
    }
}
