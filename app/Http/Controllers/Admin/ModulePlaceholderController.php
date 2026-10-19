<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ModulePlaceholderController extends Controller
{
    public function __invoke(string $module): View
    {
        $modules = [
            'products' => [
                'title' => 'Products',
                'module' => '04',
                'description' => 'Create, edit, publish, and manage SKUs, pricing, media, and SEO fields.',
            ],
            'categories' => [
                'title' => 'Categories',
                'module' => '04',
                'description' => 'Organize the catalog for shop navigation and shop-by-goal discovery.',
            ],
            'inventory' => [
                'title' => 'Inventory',
                'module' => '04',
                'description' => 'Track stock quantities, low-stock alerts, and availability status.',
            ],
            'customers' => [
                'title' => 'Customers',
                'module' => '08 / 12',
                'description' => 'Browse customer profiles, contact details, and order history.',
            ],
            'homepage' => [
                'title' => 'Homepage content',
                'module' => '06',
                'description' => 'Enable, edit, and order structured homepage sections (no page builder).',
            ],
            'pages' => [
                'title' => 'Pages',
                'module' => '06',
                'description' => 'Manage About, FAQ, Shipping, Returns, Privacy, Terms, and similar pages.',
            ],
            'blog' => [
                'title' => 'Blog',
                'module' => '07',
                'description' => 'Publish insights posts with SEO metadata and related content.',
            ],
            'media' => [
                'title' => 'Media',
                'module' => '04 / 06',
                'description' => 'Upload and organize product and content images with validation.',
            ],
            'settings' => [
                'title' => 'Settings',
                'module' => '19',
                'description' => 'Store configuration, shipping rules, tax inputs, and basic site settings.',
            ],
        ];

        abort_unless(isset($modules[$module]), 404);

        return view('admin.placeholder', $modules[$module]);
    }
}
