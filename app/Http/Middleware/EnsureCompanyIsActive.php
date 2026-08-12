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


        /*
        |--------------------------------------------------------------------------
        | Guest
        |--------------------------------------------------------------------------
        */

        if (!$user) {
            return $next($request);
        }


        /*
        |--------------------------------------------------------------------------
        | Direct Super Admin Login
        |--------------------------------------------------------------------------
        |
        | Super Admin ka company_id hona compulsory nahi hai.
        |
        */

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }


        /*
        |--------------------------------------------------------------------------
        | Super Admin Business View
        |--------------------------------------------------------------------------
        |
        | Yahan currently login user company owner/admin hoga.
        |
        | Lekin session me original Super Admin ID save hai.
        |
        | Is condition ki wajah se Super Admin inactive business ko bhi
        | inspect kar sakta hai.
        |
        */

        if (
            $request->session()->has('impersonator_id')
            &&
            $request->session()->get(
                'super_admin_business_view'
            ) === true
        ) {
            return $next($request);
        }


        /*
        |--------------------------------------------------------------------------
        | Employee/User Inactive
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {

            auth()->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Your account is inactive.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Company Missing
        |--------------------------------------------------------------------------
        */

        if (!$user->company_id) {

            auth()->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'No company is assigned to this account.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Company Not Found
        |--------------------------------------------------------------------------
        */

        if (!$user->company) {

            auth()->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Company account could not be found.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Company Inactive
        |--------------------------------------------------------------------------
        */

        if (!$user->company->is_active) {

            auth()->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Your company account is currently inactive.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Continue
        |--------------------------------------------------------------------------
        */

        return $next($request);
    }
}