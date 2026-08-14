<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminBusinessController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | View CRM As Selected Business
    |--------------------------------------------------------------------------
    |
    | Super Admin kisi bhi company ko select karke us company ke
    | company_owner ke account/context me CRM dekh sakta hai.
    |
    | Iska benefit:
    |
    | Existing controllers me agar:
    |
    | $request->user()->company_id
    |
    | ya:
    |
    | auth()->user()->company_id
    |
    | use ho raha hai, to kisi controller ko change karne ki zarurat nahi.
    |
    */
    public function viewBusiness(
        Request $request,
        Company $company
    ) {
        $superAdmin = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Only Super Admin
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $superAdmin && $superAdmin->can('companies.view-business'),
            403,
            'You do not have permission to view another business.'
        );

        /*
        |--------------------------------------------------------------------------
        | Nested Impersonation Block
        |--------------------------------------------------------------------------
        |
        | Agar already kisi employee/business ko view kar rahe hain
        | to dobara impersonation start nahi hogi.
        |
        */

        if ($request->session()->has('impersonator_id')) {
            return back()->with(
                'error',
                'You are already viewing another account. Please return to your Super Admin account first.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Find Company Owner
        |--------------------------------------------------------------------------
        |
        | Sabse pehle company_owner role wala user find karenge.
        |
        */

        $businessUser = User::query()
            ->where('company_id', $company->id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'company_owner');
            })
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Fallback: owner
        |--------------------------------------------------------------------------
        |
        | Agar old data me company_owner role assign nahi hua hai.
        |
        */

        if (!$businessUser) {
            $businessUser = User::query()
                ->where('company_id', $company->id)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'owner');
                })
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback: Admin
        |--------------------------------------------------------------------------
        */

        if (!$businessUser) {
            $businessUser = User::query()
                ->where('company_id', $company->id)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'admin');
                })
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | Last Fallback: Any Company User
        |--------------------------------------------------------------------------
        |
        | Agar company me owner/admin role data old/missing hai tab bhi
        | Super Admin company inspect kar sake.
        |
        */

        if (!$businessUser) {
            $businessUser = User::query()
                ->where('company_id', $company->id)
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | No User Found
        |--------------------------------------------------------------------------
        */

        if (!$businessUser) {
            return back()->with(
                'error',
                'This business has no user account. Please create a company owner first.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent Self Login
        |--------------------------------------------------------------------------
        */

        if ((int) $businessUser->id === (int) $superAdmin->id) {
            return back()->with(
                'error',
                'You are already logged in to this account.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Store Original Super Admin
        |--------------------------------------------------------------------------
        |
        | Existing EmployeeController::stopImpersonating() bhi
        | in session values ko understand karta hai.
        |
        */

        $request->session()->put([
            'impersonator_id' => $superAdmin->id,

            'impersonator_name' => $superAdmin->name,

            'impersonator_company_id' => $superAdmin->company_id,

            /*
            |--------------------------------------------------------------------------
            | Business View Specific Session
            |--------------------------------------------------------------------------
            */

            'super_admin_business_view' => true,

            'impersonated_company_id' => $company->id,

            'impersonated_company_name' => $company->name,

            /*
            |--------------------------------------------------------------------------
            | Existing Impersonation Session
            |--------------------------------------------------------------------------
            */

            'impersonated_user_id' => $businessUser->id,

            'impersonated_user_name' => $businessUser->name,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Login As Business User
        |--------------------------------------------------------------------------
        */

        Auth::login($businessUser);

        /*
        |--------------------------------------------------------------------------
        | Regenerate Session
        |--------------------------------------------------------------------------
        */

        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'You are now viewing ' . $company->name . '.'
            );
    }
}