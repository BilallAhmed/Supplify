<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, DashboardAnalyticsService $analytics): View
    {
        return view('admin.analytics', ['dashboard' => $analytics->build($request)]);
    }
}
