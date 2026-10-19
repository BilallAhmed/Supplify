<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use App\Support\ContactPageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactEnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContactEnquiry::class);

        $enquiries = ContactEnquiry::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('message', 'like', $term);
                });
            })
            ->when($request->filled('type') && array_key_exists((string) $request->input('type'), ContactPageContent::enquiryTypes()), function ($q) use ($request) {
                $q->where('enquiry_type', $request->input('type'));
            })
            ->when($request->input('status') === 'unread', fn ($q) => $q->unread())
            ->when($request->input('status') === 'read', fn ($q) => $q->where('is_read', true))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.contact-enquiries.index', [
            'enquiries' => $enquiries,
            'types' => ContactPageContent::enquiryTypes(),
        ]);
    }

    public function show(ContactEnquiry $contactEnquiry): View
    {
        $this->authorize('view', $contactEnquiry);
        $contactEnquiry->markRead();

        return view('admin.contact-enquiries.show', [
            'enquiry' => $contactEnquiry,
        ]);
    }

    public function destroy(ContactEnquiry $contactEnquiry): RedirectResponse
    {
        $this->authorize('delete', $contactEnquiry);
        $contactEnquiry->delete();

        return redirect()
            ->route('admin.contact-enquiries.index')
            ->with('flash_success', 'Enquiry deleted.');
    }
}
