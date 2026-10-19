<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ContactEnquiryRequest;
use App\Models\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Throwable;

class ContactEnquiryController extends Controller
{
    public function store(ContactEnquiryRequest $request): RedirectResponse
    {
        if (filled($request->input('website'))) {
            return back()
                ->withInput($request->except(['website', 'message']))
                ->with('flash_error', 'We couldn’t submit your message. Please try again.');
        }

        try {
            ContactEnquiry::query()->create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'enquiry_type' => $request->validated('enquiry_type'),
                'message' => $request->validated('message'),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500) ?: null,
            ]);
        } catch (Throwable) {
            return back()
                ->withInput($request->except('website'))
                ->with('flash_error', 'We couldn’t submit your message. Please try again.');
        }

        return redirect()
            ->route('pages.show', 'contact')
            ->with('flash_success', 'Thank you. Your message has been received.')
            ->with('contact_submitted', true);
    }
}
