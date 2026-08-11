<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsActive
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Super Admin ko allow
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Employee inactive
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {

            auth()->logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Your account is inactive.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Company missing
        |--------------------------------------------------------------------------
        */

        if (!$user->company_id) {

            auth()->logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'No company is assigned to this account.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Company inactive
        |--------------------------------------------------------------------------
        */

        if (
            !$user->company
            ||
            !$user->company->is_active
        ) {
            auth()->logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Your company account is currently inactive.',
                ]);
        }

        return $next($request);
    }
}