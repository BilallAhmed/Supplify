<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        // Role is never client-writable — public registration is always customer.
        $user->forceFill([
            'role' => UserRole::Customer,
            'is_active' => true,
        ])->save();

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();
        $this->cart->mergeForUser($user);
        app(\App\Services\WishlistService::class)->mergeForUser($user);

        return redirect()
            ->intended(route('account.dashboard'))
            ->with('flash_success', 'Welcome — your account is ready.');
    }
}
