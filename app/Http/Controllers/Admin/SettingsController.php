<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSettingsUpdateRequest;
use App\Services\StoreSettings;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(StoreSettings $settings): View
    {
        return view('admin.settings.edit', [
            'currency' => $settings->currency(),
            'shippingMethod' => $settings->shippingMethod(),
            'flatRate' => number_format($settings->flatRateCents() / 100, 2, '.', ''),
            'freeOver' => number_format($settings->freeOverCents() / 100, 2, '.', ''),
            'taxRatePercent' => rtrim(rtrim(number_format($settings->taxRatePercent(), 3, '.', ''), '0'), '.') ?: '0',
        ]);
    }

    public function update(StoreSettingsUpdateRequest $request, StoreSettings $settings): RedirectResponse
    {
        $data = $request->validated();

        $settings->putMany([
            'currency' => strtoupper($data['currency']),
            'shipping.method' => $data['shipping_method'],
            'shipping.flat_rate_cents' => Money::toCents($data['flat_rate']),
            'shipping.free_over_cents' => Money::toCents($data['free_over']),
            'tax.rate_percent' => (float) $data['tax_rate_percent'],
        ]);

        return redirect()
            ->route('admin.settings.edit')
            ->with('flash_success', 'Store settings saved.');
    }
}
