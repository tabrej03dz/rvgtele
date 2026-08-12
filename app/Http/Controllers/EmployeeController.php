<?php

// namespace App\Http\Controllers;

// use App\Models\{User, Branch, Team};
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Hash;

// class EmployeeController extends Controller
// {
//     public function index(Request $r)
//     {
//         return view('employees.index', ['employees' => User::with(['branch', 'team', 'roles'])->where('company_id', $r->user()->company_id)->latest()->paginate(20)]);
//     }
//     public function create(Request $r)
//     {
//         return view('employees.form', ['employee' => new User, 'branches' => Branch::where('company_id', $r->user()->company_id)->get(), 'teams' => Team::where('company_id', $r->user()->company_id)->get(), 'roles' => \Spatie\Permission\Models\Role::all()]);
//     }
//     public function store(Request $r)
//     {
//         $d = $r->validate(['name' => 'required', 'email' => 'required|email|unique:users', 'phone' => 'nullable', 'employee_code' => 'nullable|unique:users', 'password' => 'required|min:8', 'branch_id' => 'nullable|exists:branches,id', 'team_id' => 'nullable|exists:teams,id', 'role' => 'required']);
//         $d['company_id'] = $r->user()->company_id;
//         $d['password'] = Hash::make($d['password']);
//         $u = User::create($d);
//         $u->assignRole($d['role']);
//         return redirect()->route('employees.index')->with('success', 'Employee created.');
//     }
//     public function edit(Request $r, User $employee)
//     {
//         abort_unless($employee->company_id === $r->user()->company_id, 403);
//         return view('employees.form', ['employee' => $employee, 'branches' => Branch::where('company_id', $r->user()->company_id)->get(), 'teams' => Team::where('company_id', $r->user()->company_id)->get(), 'roles' => \Spatie\Permission\Models\Role::all()]);
//     }
//     public function update(Request $r, User $employee)
//     {
//         abort_unless($employee->company_id === $r->user()->company_id, 403);
//         $d = $r->validate(['name' => 'required', 'email' => 'required|email|unique:users,email,' . $employee->id, 'phone' => 'nullable', 'employee_code' => 'nullable|unique:users,employee_code,' . $employee->id, 'password' => 'nullable|min:8', 'branch_id' => 'nullable|exists:branches,id', 'team_id' => 'nullable|exists:teams,id', 'role' => 'required', 'is_active' => 'nullable|boolean']);
//         if (empty($d['password'])) unset($d['password']);
//         else $d['password'] = Hash::make($d['password']);
//         $employee->update($d);
//         $employee->syncRoles([$d['role']]);
//         return redirect()->route('employees.index')->with('success', 'Employee updated.');
//     }
//     public function destroy(Request $r, User $employee)
//     {
//         abort_unless($employee->company_id === $r->user()->company_id, 403);
//         abort_if($employee->id === $r->user()->id, 422, 'You cannot delete yourself.');
//         $employee->delete();
//         return back()->with('success', 'Employee deleted.');
//     }
// }



namespace App\Http\Controllers;

use App\Models\{User, Branch, Team};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Employee List
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $loggedInUser = $request->user();

        $employees = User::query()
            ->with([
                'branch',
                'team',
                'roles',
            ])
            ->where('company_id', $loggedInUser->company_id)
            ->latest()
            ->paginate(20);

        return view('employees.index', compact('employees'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create Employee
    |--------------------------------------------------------------------------
    */
    public function create(Request $request)
    {
        $companyId = $request->user()->company_id;

        return view('employees.form', [
            'employee' => new User(),

            'branches' => Branch::query()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get(),

            'teams' => Team::query()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get(),

            'roles' => Role::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Employee
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'employee_code' => [
                'nullable',
                'string',
                'max:100',
                'unique:users,employee_code',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')
                    ->where('company_id', $companyId),
            ],

            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')
                    ->where('company_id', $companyId),
            ],

            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name'),
            ],
        ]);

        $role = $data['role'];

        // role users table ka column nahi hai
        unset($data['role']);

        $data['company_id'] = $companyId;
        $data['password'] = Hash::make($data['password']);

        $employee = User::create($data);

        $employee->assignRole($role);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Employee
    |--------------------------------------------------------------------------
    */
    public function edit(Request $request, User $employee)
    {
        $companyId = $request->user()->company_id;

        abort_unless(
            (int) $employee->company_id === (int) $companyId,
            403
        );

        return view('employees.form', [
            'employee' => $employee,

            'branches' => Branch::query()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get(),

            'teams' => Team::query()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get(),

            'roles' => Role::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Employee
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, User $employee)
    {
        $companyId = $request->user()->company_id;

        abort_unless(
            (int) $employee->company_id === (int) $companyId,
            403
        );

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee->id),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'employee_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('users', 'employee_code')
                    ->ignore($employee->id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
            ],

            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')
                    ->where('company_id', $companyId),
            ],

            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')
                    ->where('company_id', $companyId),
            ],

            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name'),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $role = $data['role'];

        unset($data['role']);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // Checkbox unchecked hone par bhi inactive save ho
        $data['is_active'] = $request->boolean('is_active');

        $employee->update($data);

        $employee->syncRoles([$role]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Employee
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, User $employee)
    {
        $loggedInUser = $request->user();

        abort_unless(
            (int) $employee->company_id === (int) $loggedInUser->company_id,
            403
        );

        abort_if(
            (int) $employee->id === (int) $loggedInUser->id,
            422,
            'You cannot delete yourself.'
        );

        $employee->delete();

        return back()
            ->with('success', 'Employee deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Login / View Dashboard As Employee
    |--------------------------------------------------------------------------
    |
    | Owner:
    |     Sab employees ke dashboard me ja sakta hai.
    |
    | Admin:
    |     Team Leader aur Employee ke dashboard me ja sakta hai.
    |
    | Team Leader:
    |     Sirf apni team ke Employee ke dashboard me ja sakta hai.
    |
    */
    public function impersonate(Request $request, User $employee)
    {
        $senior = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Nested impersonation block
        |--------------------------------------------------------------------------
        */
        if ($request->session()->has('impersonator_id')) {
            return back()->with(
                'error',
                'You are already viewing another employee account.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Same company check
        |--------------------------------------------------------------------------
        */
        abort_unless(
            (int) $senior->company_id === (int) $employee->company_id,
            403,
            'You cannot access employee of another company.'
        );

        /*
        |--------------------------------------------------------------------------
        | Self impersonation not required
        |--------------------------------------------------------------------------
        */
        abort_if(
            (int) $senior->id === (int) $employee->id,
            422,
            'You are already logged in to this account.'
        );

        /*
        |--------------------------------------------------------------------------
        | Permission Check
        |--------------------------------------------------------------------------
        */
        abort_unless(
            $this->canImpersonate($senior, $employee),
            403,
            'You are not allowed to view this employee dashboard.'
        );

        /*
        |--------------------------------------------------------------------------
        | Save Senior Details
        |--------------------------------------------------------------------------
        */
        $request->session()->put([
            'impersonator_id' => $senior->id,
            'impersonator_name' => $senior->name,
            'impersonator_company_id' => $senior->company_id,

            'impersonated_user_id' => $employee->id,
            'impersonated_user_name' => $employee->name,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Login As Employee
        |--------------------------------------------------------------------------
        */
        Auth::login($employee);

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'You are now viewing ' . $employee->name . '\'s dashboard.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Exit Employee Dashboard
    |--------------------------------------------------------------------------
    */
    public function stopImpersonating(Request $request)
    {
        $seniorId = $request->session()->get('impersonator_id');

        abort_unless(
            $seniorId,
            403,
            'You are not impersonating any employee.'
        );

        $senior = User::find($seniorId);

        if (!$senior) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Original account could not be found.');
        }

        /*
        |--------------------------------------------------------------------------
        | Remove impersonation session
        |--------------------------------------------------------------------------
        */
        $request->session()->forget([
            'impersonator_id',
            'impersonator_name',
            'impersonator_company_id',
            'impersonated_user_id',
            'impersonated_user_name',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Login Back As Senior
        |--------------------------------------------------------------------------
        */
        Auth::login($senior);

        $request->session()->regenerate();

        return redirect()
            ->route('employees.index')
            ->with('success', 'You are back to your account.');
    }

    /*
    |--------------------------------------------------------------------------
    | Check Senior Permission
    |--------------------------------------------------------------------------
    */
    private function canImpersonate(User $senior, User $employee): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Same company mandatory
        |--------------------------------------------------------------------------
        */
        if (
            (int) $senior->company_id !==
            (int) $employee->company_id
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Cannot access own account
        |--------------------------------------------------------------------------
        */
        if ((int) $senior->id === (int) $employee->id) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Super Admin / Owner
        |--------------------------------------------------------------------------
        */
        if ($senior->hasAnyRole([
            'super_admin',
            'owner',
        ])) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        |
        | Admin owner/super_admin ke account me nahi ja sakta.
        */
        if ($senior->hasRole('admin')) {

            if ($employee->hasAnyRole([
                'super_admin',
                'owner',
                'admin',
            ])) {
                return false;
            }

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Team Leader
        |--------------------------------------------------------------------------
        |
        | Same team hona compulsory hai.
        | Target normal employee hona chahiye.
        */
        if ($senior->hasRole('team_leader')) {

            if (!$senior->team_id || !$employee->team_id) {
                return false;
            }

            if (
                (int) $senior->team_id !==
                (int) $employee->team_id
            ) {
                return false;
            }

            if (!$employee->hasRole('employee')) {
                return false;
            }

            return true;
        }

        return false;
    }
}