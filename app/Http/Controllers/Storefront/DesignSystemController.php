<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DesignSystemController extends Controller
{
    public function __invoke(): View
    {
        return view('storefront.design-system');
    }
}
