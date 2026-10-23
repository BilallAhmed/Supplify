<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route($request->is('admin', 'admin/*') ? 'admin.dashboard' : 'login'));
        }

        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route($request->is('admin', 'admin/*') ? 'admin.dashboard' : 'login')
                ->with('error', 'Your account is inactive. Please contact support.');
        }

        $allowed = collect($roles)
            ->map(fn (string $role) => UserRole::from($role))
            ->all();

        if (! in_array($user->role, $allowed, true)) {
            abort(403, 'You are not authorized to access this area.');
        }

        return $next($request);
    }
}
