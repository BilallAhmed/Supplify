<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $this->ensureIsNotRateLimited($request);

        $adminLogin = $request->routeIs('admin.login.store');
        $credentials = $request->only('email', 'password');
        $credentials['role'] = $adminLogin ? UserRole::Admin->value : UserRole::Customer->value;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('Your account is inactive. Please contact support.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();
        if (! $adminLogin) {
            $this->cart->mergeForUser($user);
            app(WishlistService::class)->mergeForUser($user);
        }
        $intendedPath = parse_url((string) $request->session()->get('url.intended', ''), PHP_URL_PATH) ?: '';
        $adminIntended = $intendedPath === '/admin' || str_starts_with($intendedPath, '/admin/');
        if (($adminLogin && ! $adminIntended) || (! $adminLogin && $adminIntended)) {
            $request->session()->forget('url.intended');
        }

        if ($user->role === UserRole::Admin) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()
            ->intended(route('account.dashboard'))
            ->with('flash_success', 'Signed in successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route($request->routeIs('admin.logout') ? 'admin.dashboard' : 'home')
            ->with('flash_success', 'You have been signed out.');
    }

    protected function ensureIsNotRateLimited(LoginRequest $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(LoginRequest $request): string
    {
        return ($request->routeIs('admin.login.store') ? 'admin|' : 'customer|').Str::lower($request->string('email')->toString()).'|'.$request->ip();
    }
}
