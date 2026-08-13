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
    | Higher number = Higher role
    |
    | super_admin = 5
    | owner       = 4
    | admin       = 3
    | team_leader = 2
    | employee    = 1
    |
    */

    private array $roleHierarchy = [
        'employee'    => 1,
        'team_leader' => 2,
        'admin'       => 3,
        'owner'       => 4,
        'super_admin' => 5,
    ];

    /*
    |--------------------------------------------------------------------------
    | Employee List
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $loggedInUser = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Logged In User Role Level
        |--------------------------------------------------------------------------
        */
        $loggedInLevel = $this->getUserRoleLevel($loggedInUser);

        /*
        |--------------------------------------------------------------------------
        | Logged In User se higher roles
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | Admin login:
        | blocked = owner, super_admin
        |
        | Team Leader:
        | blocked = admin, owner, super_admin
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
        | Employees Query
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
        | User apne se bada role create/select nahi kar sakta
        |--------------------------------------------------------------------------
        */

        $allowedRoles = $this->getAllowedRoleNames(
            $loggedInUser
        );

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
                ->whereIn('name', $allowedRoles)
                ->orderByRaw("
                    CASE name
                        WHEN 'super_admin' THEN 1
                        WHEN 'owner' THEN 2
                        WHEN 'admin' THEN 3
                        WHEN 'team_leader' THEN 4
                        WHEN 'employee' THEN 5
                        ELSE 6
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
                    ->where(
                        'company_id',
                        $companyId
                    ),
            ],

            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')
                    ->where(
                        'company_id',
                        $companyId
                    ),
            ],

            'role' => [
                'required',
                'string',

                /*
                |--------------------------------------------------------------------------
                | Important
                |--------------------------------------------------------------------------
                |
                | Request modify karke bhi higher role assign nahi kar sakta.
                |
                */
                Rule::in($allowedRoles),
            ],
        ]);

        $role = $data['role'];

        unset($data['role']);

        $data['company_id'] = $companyId;

        $data['password'] = Hash::make(
            $data['password']
        );

        $employee = User::create($data);

        $employee->assignRole($role);

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
        | Same Company
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $employee->company_id ===
            (int) $companyId,
            403,
            'You cannot access user of another company.'
        );

        /*
        |--------------------------------------------------------------------------
        | Higher Role Security
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $this->canManageUser(
                $loggedInUser,
                $employee
            ),
            403,
            'You cannot access a user with a higher role.'
        );

        $allowedRoles = $this->getAllowedRoleNames(
            $loggedInUser
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

            /*
            |--------------------------------------------------------------------------
            | Higher roles dropdown me bhi nahi aayenge
            |--------------------------------------------------------------------------
            */

            'roles' => Role::query()
                ->whereIn('name', $allowedRoles)
                ->orderByRaw("
                    CASE name
                        WHEN 'super_admin' THEN 1
                        WHEN 'owner' THEN 2
                        WHEN 'admin' THEN 3
                        WHEN 'team_leader' THEN 4
                        WHEN 'employee' THEN 5
                        ELSE 6
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
        | Same Company
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $employee->company_id ===
            (int) $companyId,
            403,
            'You cannot access user of another company.'
        );

        /*
        |--------------------------------------------------------------------------
        | Higher Role Security
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $this->canManageUser(
                $loggedInUser,
                $employee
            ),
            403,
            'You cannot update a user with a higher role.'
        );

        $allowedRoles = $this->getAllowedRoleNames(
            $loggedInUser
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

                Rule::unique(
                    'users',
                    'email'
                )->ignore($employee->id),
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
                )->ignore($employee->id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
            ],

            'branch_id' => [
                'nullable',

                Rule::exists(
                    'branches',
                    'id'
                )->where(
                    'company_id',
                    $companyId
                ),
            ],

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

            'role' => [
                'required',
                'string',

                /*
                |--------------------------------------------------------------------------
                | Higher role manually submit nahi ho sakta
                |--------------------------------------------------------------------------
                */

                Rule::in($allowedRoles),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

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
        | Active Status
        |--------------------------------------------------------------------------
        */

        $data['is_active'] =
            $request->boolean('is_active');

        $employee->update($data);

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
        | Same Company
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $employee->company_id ===
            (int) $loggedInUser->company_id,
            403,
            'You cannot access user of another company.'
        );

        /*
        |--------------------------------------------------------------------------
        | Higher Role Security
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $this->canManageUser(
                $loggedInUser,
                $employee
            ),
            403,
            'You cannot delete a user with a higher role.'
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

        $employee->delete();

        return back()
            ->with(
                'success',
                'Employee deleted successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Login / View Dashboard As Employee
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
        | Same Company
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $senior->company_id ===
            (int) $employee->company_id,
            403,
            'You cannot access employee of another company.'
        );

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
        | Save Original User
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

        Auth::login($employee);

        $request->session()->regenerate();

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
    | Exit Employee Dashboard
    |--------------------------------------------------------------------------
    */
    public function stopImpersonating(
        Request $request
    ) {
        $seniorId =
            $request->session()
                ->get('impersonator_id');

        abort_unless(
            $seniorId,
            403,
            'You are not impersonating any employee.'
        );

        $senior = User::find($seniorId);

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
        | Remove Impersonation Session
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

        Auth::login($senior);

        $request->session()->regenerate();

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
        | Self
        |--------------------------------------------------------------------------
        */

        if (
            (int) $senior->id ===
            (int) $employee->id
        ) {
            return false;
        }

        $seniorLevel =
            $this->getUserRoleLevel($senior);

        $employeeLevel =
            $this->getUserRoleLevel($employee);

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Dashboard impersonation ke liye target role
        | logged-in user se LOWER hona compulsory hai.
        |
        | Same level account me enter nahi kar sakta.
        |
        */

        if ($employeeLevel >= $seniorLevel) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Team Leader Special Rule
        |--------------------------------------------------------------------------
        |
        | Team leader sirf SAME TEAM ke employee ko open karega.
        |
        */

        if ($senior->hasRole('team_leader')) {

            if (
                !$senior->team_id ||
                !$employee->team_id
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
                !$employee->hasRole('employee')
            ) {
                return false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Employee kisi ka dashboard impersonate nahi karega
        |--------------------------------------------------------------------------
        */

        if ($senior->hasRole('employee')) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Can Manage / See User
    |--------------------------------------------------------------------------
    |
    | Same level allowed.
    | Higher level NOT allowed.
    |
    */
    private function canManageUser(
        User $loggedInUser,
        User $targetUser
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Company Check
        |--------------------------------------------------------------------------
        */

        if (
            (int) $loggedInUser->company_id !==
            (int) $targetUser->company_id
        ) {
            return false;
        }

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
        | Target bada role hai to deny
        |--------------------------------------------------------------------------
        */

        if ($targetLevel > $loggedInLevel) {
            return false;
        }

        return true;
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

            if ($level > $highestLevel) {
                $highestLevel = $level;
            }
        }

        return $highestLevel;
    }

    /*
    |--------------------------------------------------------------------------
    | Allowed Role Names
    |--------------------------------------------------------------------------
    |
    | Logged user apne role level ya usse niche ke roles
    | select/create kar sakta hai.
    |
    */
    private function getAllowedRoleNames(
        User $user
    ): array {

        $loggedInLevel =
            $this->getUserRoleLevel($user);

        return collect(
            $this->roleHierarchy
        )
            ->filter(
                fn ($level) =>
                    $level <= $loggedInLevel
            )
            ->keys()
            ->values()
            ->all();
    }
}