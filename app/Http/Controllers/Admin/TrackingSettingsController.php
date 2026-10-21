<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrackingSettingsUpdateRequest;
use App\Models\SettingsAudit;
use App\Models\User;
use App\Services\TrackingSettings;
use App\Services\TrackingSnippetGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrackingSettingsController extends Controller
{
    public function edit(
        TrackingSettings $settings,
        TrackingSnippetGenerator $generator,
    ): View {
        $config = $settings->all();
        $updatedBy = null;

        if ($config['updated_by']) {
            $updatedBy = User::query()->find($config['updated_by']);
        }

        $recentAudits = SettingsAudit::query()
            ->with('user:id,name,email')
            ->where('group', TrackingSettings::GROUP)
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('admin.settings.tracking', [
            'config' => $config,
            'updatedBy' => $updatedBy,
            'recentAudits' => $recentAudits,
            'preview' => [
                'gtm_head' => $config['gtm']['container_id']
                    ? $generator->gtmHead($config['gtm']['container_id'])
                    : null,
                'ga4_head' => $config['ga4']['measurement_id']
                    ? $generator->ga4Head($config['ga4']['measurement_id'])
                    : null,
                'meta_head' => $config['meta']['pixel_id']
                    ? $generator->metaPixelHead($config['meta']['pixel_id'])
                    : null,
            ],
            'allowedEnvironments' => config('tracking.allowed_environments', ['production', 'staging', 'local']),
            'currentEnvironment' => app()->environment(),
            'forceDisabled' => (bool) config('tracking.force_disable'),
        ]);
    }

    public function update(
        TrackingSettingsUpdateRequest $request,
        TrackingSettings $settings,
    ): RedirectResponse {
        $settings->save($request->trackingPayload(), $request->user());

        return redirect()
            ->route('admin.settings.tracking.edit')
            ->with('flash_success', 'Tracking & Scripts settings saved.');
    }
}
