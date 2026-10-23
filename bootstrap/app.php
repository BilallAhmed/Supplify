<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\PreventBrowserCache;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Set by the storefront consent banner (plain JSON); must not be encrypted.
        $middleware->encryptCookies(except: [
            'supplify_consent',
        ]);

        $middleware->append(PreventBrowserCache::class);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*') ? route('admin.dashboard') : route('login'));
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();

            if ($user?->isAdmin()) {
                return route('admin.dashboard');
            }

            return route('account.profile.edit');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
