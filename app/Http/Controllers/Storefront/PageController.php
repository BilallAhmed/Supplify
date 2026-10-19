<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Support\AboutPageContent;
use App\Support\ContactPageContent;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
    ) {}

    public function show(string $page): View
    {
        $item = $this->content->findPublishedPage($page);

        if ($item->isAboutPage()) {
            return view('storefront.pages.about', [
                'page' => $item,
                'about' => AboutPageContent::resolve($item),
            ]);
        }

        if ($item->isContactPage()) {
            $contact = ContactPageContent::resolve($item);

            return view('storefront.pages.contact', [
                'page' => $item,
                'contact' => $contact,
                'enquiryTypes' => ContactPageContent::enquiryTypes(),
                'allowsDirectLinks' => ContactPageContent::allowsDirectLinks($contact['details'] ?? []),
            ]);
        }

        return view('storefront.pages.show', [
            'page' => $item,
        ]);
    }
}
