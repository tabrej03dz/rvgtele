<?php

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
    | Role Hierarchy
    |--------------------------------------------------------------------------
    |
    | Higher number = Higher / Senior role
    |
    | super_admin   = 6
    | owner         = 5
    | admin         = 4
    | sales_manager = 3
    | team_leader   = 2
    | employee      = 1
    |
    */

    private array $roleHierarchy = [
        'employee'      => 1,
        'team_leader'   => 2,
        'sales_manager' => 3,
        'admin'         => 4,
        'owner'         => 5,
        'super_admin'   => 6,
    ];


    /*
    |--------------------------------------------------------------------------
    | Employee List
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $loggedInUser = $request->user();

        $loggedInLevel = $this->getUserRoleLevel(
            $loggedInUser
        );

        /*
        |--------------------------------------------------------------------------
        | Higher Roles
        |--------------------------------------------------------------------------
        |
        | Logged user se bade role list me nahi dikhenge.
        |
        | Example:
        |
        | sales_manager:
        | admin, owner, super_admin hide
        |
        | team_leader:
        | sales_manager, admin, owner, super_admin hide
        |
        */

        $blockedRoles = collect($this->roleHierarchy)
            ->filter(
                fn ($level) =>
                    $level > $loggedInLevel
            )
            ->keys()
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $employees = User::query()
            ->with([
                'branch',
                'team',
                'roles',
            ])
            ->where(
                'company_id',
                $loggedInUser->company_id
            );


        /*
        |--------------------------------------------------------------------------
        | Branch Restriction
        |--------------------------------------------------------------------------
        |
        | User sirf apni branch ke users dekhega.
        |
        */

        if (!empty($loggedInUser->branch_id)) {

            $employees->where(
                'branch_id',
                $loggedInUser->branch_id
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | Agar logged user ki branch NULL hai
            |--------------------------------------------------------------------------
            */

            $employees->whereNull(
                'branch_id'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Higher Role Users Hide
        |--------------------------------------------------------------------------
        */

        if (!empty($blockedRoles)) {

            $employees->whereDoesntHave(
                'roles',
                function ($query) use ($blockedRoles) {

                    $query->whereIn(
                        'name',
                        $blockedRoles
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $employees = $employees
            ->latest()
            ->paginate(20)
            ->withQueryString();


        return view(
            'employees.index',
            compact('employees')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Employee
    |--------------------------------------------------------------------------
    */
    public function create(Request $request)
    {
        $loggedInUser = $request->user();

        $companyId = $loggedInUser->company_id;


        /*
        |--------------------------------------------------------------------------
        | Allowed Roles
        |--------------------------------------------------------------------------
        |
        | Logged user apne level ya usse neeche ka role bana sakta hai.
        |
        | Sales Manager:
        |
        | sales_manager
        | team_leader
        | employee
        |
        */

        $allowedRoles = $this->getAllowedRoleNames(
            $loggedInUser
        );


        /*
        |--------------------------------------------------------------------------
        | Branches
        |--------------------------------------------------------------------------
        |
        | Sirf logged-in user's branch.
        |
        */

        $branches = Branch::query()
            ->where(
                'company_id',
                $companyId
            );


        if (!empty($loggedInUser->branch_id)) {

            $branches->where(
                'id',
                $loggedInUser->branch_id
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | Branch assigned nahi hai to koi branch list nahi
            |--------------------------------------------------------------------------
            */

            $branches->whereRaw('1 = 0');
        }


        return view('employees.form', [

            'employee' => new User(),

            'branches' => $branches
                ->orderBy('name')
                ->get(),

            'teams' => Team::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->orderBy('name')
                ->get(),

            /*
            |--------------------------------------------------------------------------
            | Roles
            |--------------------------------------------------------------------------
            */

            'roles' => Role::query()
                ->whereIn(
                    'name',
                    $allowedRoles
                )
                ->orderByRaw("
                    CASE name
                        WHEN 'super_admin' THEN 1
                        WHEN 'owner' THEN 2
                        WHEN 'admin' THEN 3
                        WHEN 'sales_manager' THEN 4
                        WHEN 'team_leader' THEN 5
                        WHEN 'employee' THEN 6
                        ELSE 7
                    END
                ")
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
        $loggedInUser = $request->user();

        $companyId = $loggedInUser->company_id;


        /*
        |--------------------------------------------------------------------------
        | Allowed Roles
        |--------------------------------------------------------------------------
        */

        $allowedRoles = $this->getAllowedRoleNames(
            $loggedInUser
        );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

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

            /*
            |--------------------------------------------------------------------------
            | Branch
            |--------------------------------------------------------------------------
            |
            | Frontend branch value par trust nahi karenge.
            | Neeche logged user ki branch force hogi.
            |
            */

            'branch_id' => [
                'nullable',
            ],

            /*
            |--------------------------------------------------------------------------
            | Team
            |--------------------------------------------------------------------------
            */

            'team_id' => [
                'nullable',

                Rule::exists(
                    'teams',
                    'id'
                )->where(
                    'company_id',
                    $companyId
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Role
            |--------------------------------------------------------------------------
            |
            | Manually higher role request me bhejne par validation fail hogi.
            |
            */

            'role' => [
                'required',
                'string',
                Rule::in($allowedRoles),
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Separate Role
        |--------------------------------------------------------------------------
        */

        $role = $data['role'];

        unset($data['role']);


        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        $data['company_id'] =
            $companyId;


        /*
        |--------------------------------------------------------------------------
        | Force Branch
        |--------------------------------------------------------------------------
        |
        | Employee logged user ki branch me hi create hoga.
        |
        */

        $data['branch_id'] =
            $loggedInUser->branch_id;


        /*
        |--------------------------------------------------------------------------
        | Password Hash
        |--------------------------------------------------------------------------
        */

        $data['password'] = Hash::make(
            $data['password']
        );


        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */

        $employee = User::create(
            $data
        );


        /*
        |--------------------------------------------------------------------------
        | Assign Role
        |--------------------------------------------------------------------------
        */

        $employee->assignRole(
            $role
        );


        return redirect()
            ->route('employees.index')
            ->with(
                'success',
                'Employee created successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Edit Employee
    |--------------------------------------------------------------------------
    */
    public function edit(
        Request $request,
        User $employee
    ) {
        $loggedInUser = $request->user();

        $companyId = $loggedInUser->company_id;


        /*
        |--------------------------------------------------------------------------
        | Access Check
        |--------------------------------------------------------------------------
        |
        | Same Company
        | Same Branch
        | Higher Role Block
        |
        */

        abort_unless(
            $this->canManageUser(
                $loggedInUser,
                $employee
            ),
            403,
            'You are not allowed to access this employee.'
        );


        /*
        |--------------------------------------------------------------------------
        | Allowed Roles
        |--------------------------------------------------------------------------
        */

        $allowedRoles = $this->getAllowedRoleNames(
            $loggedInUser
        );


        /*
        |--------------------------------------------------------------------------
        | Branches
        |--------------------------------------------------------------------------
        */

        $branches = Branch::query()
            ->where(
                'company_id',
                $companyId
            );


        if (!empty($loggedInUser->branch_id)) {

            $branches->where(
                'id',
                $loggedInUser->branch_id
            );

        } else {

            $branches->whereRaw('1 = 0');
        }


        return view('employees.form', [

            'employee' => $employee,

            'branches' => $branches
                ->orderBy('name')
                ->get(),

            'teams' => Team::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->orderBy('name')
                ->get(),

            'roles' => Role::query()
                ->whereIn(
                    'name',
                    $allowedRoles
                )
                ->orderByRaw("
                    CASE name
                        WHEN 'super_admin' THEN 1
                        WHEN 'owner' THEN 2
                        WHEN 'admin' THEN 3
                        WHEN 'sales_manager' THEN 4
                        WHEN 'team_leader' THEN 5
                        WHEN 'employee' THEN 6
                        ELSE 7
                    END
                ")
                ->get(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Employee
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        User $employee
    ) {
        $loggedInUser = $request->user();

        $companyId = $loggedInUser->company_id;


        /*
        |--------------------------------------------------------------------------
        | Access Check
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $this->canManageUser(
                $loggedInUser,
                $employee
            ),
            403,
            'You are not allowed to update this employee.'
        );


        /*
        |--------------------------------------------------------------------------
        | Allowed Roles
        |--------------------------------------------------------------------------
        */

        $allowedRoles = $this->getAllowedRoleNames(
            $loggedInUser
        );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

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

                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $employee->id
                ),
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

                Rule::unique(
                    'users',
                    'employee_code'
                )->ignore(
                    $employee->id
                ),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
            ],

            /*
            |--------------------------------------------------------------------------
            | Branch
            |--------------------------------------------------------------------------
            */

            'branch_id' => [
                'nullable',
            ],

            /*
            |--------------------------------------------------------------------------
            | Team
            |--------------------------------------------------------------------------
            */

            'team_id' => [
                'nullable',

                Rule::exists(
                    'teams',
                    'id'
                )->where(
                    'company_id',
                    $companyId
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Role
            |--------------------------------------------------------------------------
            */

            'role' => [
                'required',
                'string',
                Rule::in($allowedRoles),
            ],

            /*
            |--------------------------------------------------------------------------
            | Active
            |--------------------------------------------------------------------------
            */

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Role
        |--------------------------------------------------------------------------
        */

        $role = $data['role'];

        unset($data['role']);


        /*
        |--------------------------------------------------------------------------
        | Password
        |--------------------------------------------------------------------------
        */

        if (empty($data['password'])) {

            unset($data['password']);

        } else {

            $data['password'] = Hash::make(
                $data['password']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Force Branch
        |--------------------------------------------------------------------------
        |
        | User employee ko doosri branch me move nahi kar sakta.
        |
        */

        $data['branch_id'] =
            $loggedInUser->branch_id;


        /*
        |--------------------------------------------------------------------------
        | Active Status
        |--------------------------------------------------------------------------
        */

        $data['is_active'] =
            $request->boolean(
                'is_active'
            );


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $employee->update(
            $data
        );


        /*
        |--------------------------------------------------------------------------
        | Role Update
        |--------------------------------------------------------------------------
        */

        $employee->syncRoles([
            $role
        ]);


        return redirect()
            ->route('employees.index')
            ->with(
                'success',
                'Employee updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Employee
    |--------------------------------------------------------------------------
    */
    public function destroy(
        Request $request,
        User $employee
    ) {
        $loggedInUser = $request->user();


        /*
        |--------------------------------------------------------------------------
        | Access Check
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $this->canManageUser(
                $loggedInUser,
                $employee
            ),
            403,
            'You are not allowed to delete this employee.'
        );


        /*
        |--------------------------------------------------------------------------
        | Self Delete Block
        |--------------------------------------------------------------------------
        */

        abort_if(
            (int) $employee->id ===
            (int) $loggedInUser->id,
            422,
            'You cannot delete yourself.'
        );


        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $employee->delete();


        return back()
            ->with(
                'success',
                'Employee deleted successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Login / Employee View
    |--------------------------------------------------------------------------
    */
    public function impersonate(
        Request $request,
        User $employee
    ) {
        $senior = $request->user();


        /*
        |--------------------------------------------------------------------------
        | Nested Impersonation Block
        |--------------------------------------------------------------------------
        */

        if (
            $request->session()
                ->has('impersonator_id')
        ) {

            return back()->with(
                'error',
                'You are already viewing another employee account.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Self Impersonation
        |--------------------------------------------------------------------------
        */

        abort_if(
            (int) $senior->id ===
            (int) $employee->id,
            422,
            'You are already logged in to this account.'
        );


        /*
        |--------------------------------------------------------------------------
        | Permission Check
        |--------------------------------------------------------------------------
        |
        | Same company
        | Same branch
        | Target lower role
        |
        | Team Leader:
        | same team employee only
        |
        */

        abort_unless(
            $this->canImpersonate(
                $senior,
                $employee
            ),
            403,
            'You are not allowed to view this employee dashboard.'
        );


        /*
        |--------------------------------------------------------------------------
        | Save Original Account
        |--------------------------------------------------------------------------
        */

        $request->session()->put([

            'impersonator_id' =>
                $senior->id,

            'impersonator_name' =>
                $senior->name,

            'impersonator_company_id' =>
                $senior->company_id,

            'impersonated_user_id' =>
                $employee->id,

            'impersonated_user_name' =>
                $employee->name,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Login As Employee
        |--------------------------------------------------------------------------
        */

        Auth::login(
            $employee
        );

        $request->session()
            ->regenerate();


        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'You are now viewing '
                . $employee->name
                . '\'s dashboard.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Stop Impersonating
    |--------------------------------------------------------------------------
    */
    public function stopImpersonating(
        Request $request
    ) {
        $seniorId =
            $request->session()
                ->get(
                    'impersonator_id'
                );


        abort_unless(
            $seniorId,
            403,
            'You are not impersonating any employee.'
        );


        /*
        |--------------------------------------------------------------------------
        | Find Original User
        |--------------------------------------------------------------------------
        */

        $senior = User::find(
            $seniorId
        );


        if (!$senior) {

            Auth::logout();

            $request->session()
                ->invalidate();

            $request->session()
                ->regenerateToken();


            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Original account could not be found.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Clear Impersonation Session
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
        | Login Back
        |--------------------------------------------------------------------------
        */

        Auth::login(
            $senior
        );

        $request->session()
            ->regenerate();


        return redirect()
            ->route('employees.index')
            ->with(
                'success',
                'You are back to your account.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Can Impersonate
    |--------------------------------------------------------------------------
    */
    private function canImpersonate(
        User $senior,
        User $employee
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Same Company
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
        | Same Branch
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isSameBranch(
                $senior,
                $employee
            )
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Self
        |--------------------------------------------------------------------------
        */

        if (
            (int) $senior->id ===
            (int) $employee->id
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Role Levels
        |--------------------------------------------------------------------------
        */

        $seniorLevel =
            $this->getUserRoleLevel(
                $senior
            );

        $employeeLevel =
            $this->getUserRoleLevel(
                $employee
            );


        /*
        |--------------------------------------------------------------------------
        | Target Must Be Lower Role
        |--------------------------------------------------------------------------
        |
        | Same role ka dashboard bhi impersonate nahi hoga.
        |
        */

        if (
            $employeeLevel >=
            $seniorLevel
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Employee Cannot Impersonate
        |--------------------------------------------------------------------------
        */

        if (
            $senior->hasRole(
                'employee'
            )
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Team Leader Special Restriction
        |--------------------------------------------------------------------------
        |
        | Team Leader:
        |
        | Same branch
        | Same team
        | Employee role only
        |
        */

        if (
            $senior->hasRole(
                'team_leader'
            )
        ) {

            if (
                empty($senior->team_id) ||
                empty($employee->team_id)
            ) {
                return false;
            }


            if (
                (int) $senior->team_id !==
                (int) $employee->team_id
            ) {
                return false;
            }


            if (
                !$employee->hasRole(
                    'employee'
                )
            ) {
                return false;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Sales Manager
        |--------------------------------------------------------------------------
        |
        | Generic hierarchy automatically allow karegi:
        |
        | sales_manager -> team_leader
        | sales_manager -> employee
        |
        | Same branch already mandatory hai.
        |
        */

        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Can Manage User
    |--------------------------------------------------------------------------
    |
    | Same company
    | Same branch
    | Target higher role nahi
    |
    */
    private function canManageUser(
        User $loggedInUser,
        User $targetUser
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Same Company
        |--------------------------------------------------------------------------
        */

        if (
            (int) $loggedInUser->company_id !==
            (int) $targetUser->company_id
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Same Branch
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isSameBranch(
                $loggedInUser,
                $targetUser
            )
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Levels
        |--------------------------------------------------------------------------
        */

        $loggedInLevel =
            $this->getUserRoleLevel(
                $loggedInUser
            );

        $targetLevel =
            $this->getUserRoleLevel(
                $targetUser
            );


        /*
        |--------------------------------------------------------------------------
        | Higher Role Block
        |--------------------------------------------------------------------------
        */

        if (
            $targetLevel >
            $loggedInLevel
        ) {
            return false;
        }


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Same Branch Check
    |--------------------------------------------------------------------------
    */
    private function isSameBranch(
        User $loggedInUser,
        User $targetUser
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        if (
            (int) $loggedInUser->company_id !==
            (int) $targetUser->company_id
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Both Branch NULL
        |--------------------------------------------------------------------------
        */

        if (
            empty($loggedInUser->branch_id) &&
            empty($targetUser->branch_id)
        ) {
            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | One Branch NULL
        |--------------------------------------------------------------------------
        */

        if (
            empty($loggedInUser->branch_id) ||
            empty($targetUser->branch_id)
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Compare Branch
        |--------------------------------------------------------------------------
        */

        return
            (int) $loggedInUser->branch_id ===
            (int) $targetUser->branch_id;
    }


    /*
    |--------------------------------------------------------------------------
    | Get User Highest Role Level
    |--------------------------------------------------------------------------
    */
    private function getUserRoleLevel(
        User $user
    ): int {

        $highestLevel = 0;


        foreach (
            $user->getRoleNames()
            as $roleName
        ) {

            $level =
                $this->roleHierarchy[
                    $roleName
                ] ?? 0;


            if (
                $level >
                $highestLevel
            ) {

                $highestLevel =
                    $level;
            }
        }


        return $highestLevel;
    }


    /*
    |--------------------------------------------------------------------------
    | Get Allowed Role Names
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Super Admin:
    | all roles
    |
    | Owner:
    | owner, admin, sales_manager, team_leader, employee
    |
    | Admin:
    | admin, sales_manager, team_leader, employee
    |
    | Sales Manager:
    | sales_manager, team_leader, employee
    |
    | Team Leader:
    | team_leader, employee
    |
    | Employee:
    | employee
    |
    */
    private function getAllowedRoleNames(
        User $user
    ): array {

        $loggedInLevel =
            $this->getUserRoleLevel(
                $user
            );


        return collect(
            $this->roleHierarchy
        )
            ->filter(
                fn ($level) =>
                    $level <=
                    $loggedInLevel
            )
            ->keys()
            ->values()
            ->all();
    }
}