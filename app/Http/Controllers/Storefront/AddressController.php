<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddressRequest;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Address::class);

        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('storefront.account.addresses.index', compact('addresses'));
    }

    public function create(): View
    {
        $this->authorize('create', Address::class);

        return view('storefront.account.addresses.create', [
            'address' => new Address([
                'type' => 'shipping',
                'country' => 'PK',
                'is_default' => false,
            ]),
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $this->authorize('create', Address::class);

        $this->persist($request, new Address);

        return redirect()
            ->route('account.addresses.index')
            ->with('flash_success', 'Address saved.');
    }

    public function edit(Address $address): View
    {
        $this->authorize('update', $address);

        return view('storefront.account.addresses.edit', compact('address'));
    }

    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);

        $this->persist($request, $address);

        return redirect()
            ->route('account.addresses.index')
            ->with('flash_success', 'Address updated.');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorize('delete', $address);

        $address->delete();

        return redirect()
            ->route('account.addresses.index')
            ->with('flash_success', 'Address removed.');
    }

    private function persist(AddressRequest $request, Address $address): void
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $address, $data) {
            if (! empty($data['is_default'])) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address->fill($data);
            $address->user_id = $user->id;

            if (! $user->addresses()->exists()) {
                $address->is_default = true;
            }

            $address->save();
        });
    }
}
