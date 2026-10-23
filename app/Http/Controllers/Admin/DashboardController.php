<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StoreOverviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StoreOverviewService $overview): View|RedirectResponse
    {
        // /admin is both the dedicated sign-in entry and the authenticated landing page.
        if (! $request->user()) {
            return view('admin.auth.login');
        }
        if (! $request->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.dashboard')->withErrors(['email' => 'Your account is inactive. Please contact support.']);
        }
        abort_unless($request->user()->isAdmin(), 403);

        return view('admin.dashboard', ['overview' => $overview->build()]);
    }
}
