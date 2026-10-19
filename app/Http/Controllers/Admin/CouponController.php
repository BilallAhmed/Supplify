<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Coupon::class);

        $coupons = Coupon::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('code', 'like', $term);
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        $this->authorize('create', Coupon::class);

        return view('admin.coupons.create', [
            'coupon' => new Coupon([
                'type' => CouponType::Percent,
                'value' => 10,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        Coupon::query()->create($request->couponAttributes());

        return redirect()
            ->route('admin.coupons.index')
            ->with('flash_success', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorize('update', $coupon);

        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->authorize('update', $coupon);

        $coupon->update($request->couponAttributes());

        return redirect()
            ->route('admin.coupons.index')
            ->with('flash_success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('flash_success', 'Coupon deleted.');
    }
}
