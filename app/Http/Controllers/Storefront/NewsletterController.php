<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\NewsletterSubscribeRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;

class NewsletterController extends Controller
{
    public function store(NewsletterSubscribeRequest $request): RedirectResponse
    {
        $email = strtolower(trim($request->validated('email')));

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $email]);
        $subscriber->fill([
            'source' => 'homepage',
            'is_active' => true,
            'subscribed_at' => $subscriber->subscribed_at ?? now(),
        ]);
        $subscriber->save();

        return back()->with('flash_success', 'You are subscribed. Welcome aboard.');
    }
}
