<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $base = User::query()->where('role', UserRole::Customer);

        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
            'with_orders' => (clone $base)->whereHas('orders')->count(),
        ];

        $customers = (clone $base)
            ->withCount(['orders', 'addresses'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->when($request->input('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'stats' => $stats,
        ]);
    }

    public function show(User $customer): View
    {
        $this->ensureCustomer($customer);
        $this->authorize('view', $customer);

        $customer->load(['addresses' => fn ($q) => $q->orderByDesc('is_default')->orderBy('id')]);

        $orders = $customer->orders()
            ->withCount('items')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    public function update(CustomerUpdateRequest $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);
        $this->authorize('update', $customer);

        $data = $request->validated();

        $customer->name = $data['name'];
        $customer->phone = $data['phone'] ?? null;
        if (array_key_exists('is_active', $data)) {
            $customer->is_active = (bool) $data['is_active'];
        }
        $customer->save();

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('flash_success', 'Customer updated.');
    }

    private function ensureCustomer(User $customer): void
    {
        abort_unless($customer->isCustomer(), 404);
    }
}
