<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAnalyticsService $analytics,
    ) {}

    public function __invoke(Request $request): View
    {
        $dashboard = $this->analytics->build($request);

        return view('admin.dashboard', [
            'dashboard' => $dashboard,
        ]);
    }
}
