<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssistantSourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssistantSourceApprovalRequest;
use App\Models\AssistantFaq;
use App\Models\AssistantSourceApproval;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Services\Assistant\AssistantEligibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssistantApprovalController extends Controller
{
    public function index(Request $request, AssistantEligibility $eligibility): View
    {
        $this->authorize('approveSources', AssistantFaq::class);
        $type = $request->string('type', 'product')->toString();
        abort_unless(in_array($type, ['product', 'post', 'page', 'category'], true), 404);
        $model = match ($type) {
            'product' => Product::class, 'post' => Post::class,
            'page' => Page::class, 'category' => Category::class,
        };
        $query = $model::query();
        $field = in_array($type, ['product', 'category'], true) ? 'name' : 'title';
        if ($request->filled('q')) {
            $query->where($field, 'like', '%'.addcslashes(mb_substr($request->string('q')->toString(), 0, 120), '%_\\').'%');
        }
        $sources = $query->orderBy($field)->paginate(20)->withQueryString();
        $approvals = AssistantSourceApproval::where('source_type', $type)->whereIn('source_id', $sources->pluck('id'))->pluck('approved_at', 'source_id');

        return view('admin.assistant.sources', compact('sources', 'type', 'approvals', 'eligibility'));
    }

    public function store(AssistantSourceApprovalRequest $request, AssistantEligibility $eligibility): RedirectResponse
    {
        $source = $this->resolveSource(
            AssistantSourceType::from($request->validated('source_type')),
            (int) $request->validated('source_id'),
        );

        $eligibility->approve($source, $request->user());

        return back()->with('status', 'Source approved for Supplify Assistant.');
    }

    public function destroy(AssistantSourceApprovalRequest $request, AssistantEligibility $eligibility): RedirectResponse
    {
        $source = $this->resolveSource(
            AssistantSourceType::from($request->validated('source_type')),
            (int) $request->validated('source_id'),
        );

        $eligibility->withdraw($source);

        return back()->with('status', 'Assistant approval withdrawn.');
    }

    private function resolveSource(AssistantSourceType $type, int $id): Model
    {
        $source = match ($type) {
            AssistantSourceType::Product => Product::query()->withTrashed()->find($id),
            AssistantSourceType::Post => Post::query()->withTrashed()->find($id),
            AssistantSourceType::Page => Page::query()->withTrashed()->find($id),
            AssistantSourceType::Category => Category::query()->withTrashed()->find($id),
            AssistantSourceType::Faq => AssistantFaq::query()->withTrashed()->find($id),
        };

        if ($source === null) {
            throw ValidationException::withMessages([
                'source_id' => 'The selected source does not exist.',
            ]);
        }

        return $source;
    }
}
