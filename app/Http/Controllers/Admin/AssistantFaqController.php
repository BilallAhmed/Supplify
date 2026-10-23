<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssistantIntent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssistantFaqRequest;
use App\Models\AssistantFaq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantFaqController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AssistantFaq::class);

        $faqs = AssistantFaq::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('question', 'like', $term)
                        ->orWhere('answer', 'like', $term);
                });
            })
            ->when($request->input('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('question')
            ->paginate(20)
            ->withQueryString();

        return view('admin.assistant.faqs.index', compact('faqs'));
    }

    public function create(): View
    {
        $this->authorize('create', AssistantFaq::class);

        return view('admin.assistant.faqs.create', [
            'faq' => new AssistantFaq([
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'intents' => AssistantIntent::cases(),
        ]);
    }

    public function store(AssistantFaqRequest $request): RedirectResponse
    {
        AssistantFaq::query()->create($request->faqAttributes());

        return redirect()
            ->route('admin.assistant.faqs.index')
            ->with('flash_success', 'FAQ created. Active FAQs are eligible for the knowledge index.');
    }

    public function edit(AssistantFaq $faq): View
    {
        $this->authorize('update', $faq);

        return view('admin.assistant.faqs.edit', [
            'faq' => $faq,
            'intents' => AssistantIntent::cases(),
        ]);
    }

    public function update(AssistantFaqRequest $request, AssistantFaq $faq): RedirectResponse
    {
        $faq->update($request->faqAttributes());

        return redirect()
            ->route('admin.assistant.faqs.index')
            ->with('flash_success', 'FAQ updated.');
    }

    public function destroy(AssistantFaq $faq): RedirectResponse
    {
        $this->authorize('delete', $faq);
        $faq->delete();

        return redirect()
            ->route('admin.assistant.faqs.index')
            ->with('flash_success', 'FAQ deleted.');
    }
}
