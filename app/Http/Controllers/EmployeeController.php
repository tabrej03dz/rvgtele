<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /**
     * Higher number = higher/senior role.
     */
    private array $roleHierarchy = [
        'employee'      => 1,
        'team_leader'   => 2,
        'sales_manager' => 3,
        'admin'         => 4,
        'owner'         => 5,
        'super_admin'   => 6,
    ];

    /**
     * Employee listing rules:
     *
     * super_admin   => all companies / branches / teams / roles
     * owner         => own company, all branches/teams, lower roles
     * admin         => own company + own branch (if assigned), lower roles
     * sales_manager => own company + own branch + own team (when assigned), lower roles
     * team_leader   => own company + own branch + own team, lower roles
     * employee      => only own account
     */
    public function index(Request $request)
    {
        $loggedInUser = $request->user();

        $employeesQuery = User::query()
            ->with([
                'company:id,name',
                'branch:id,company_id,name',
                'team:id,company_id,name',
                'roles:id,name,guard_name',
            ]);

        $this->applyEmployeeVisibilityScope(
            $employeesQuery,
            $loggedInUser
        );

        $employees = $employeesQuery
            ->latest('users.id')
            ->paginate(20)
            ->withQueryString();

        /*
         * Blade me hierarchy dobara duplicate na karni pade,
         * isliye action flags controller se bhej rahe hain.
         */
        $employees->getCollection()->transform(
            function (User $employee) use ($loggedInUser) {
                $employee->can_employee_view = $this->canImpersonate(
                    $loggedInUser,
                    $employee
                );

                $employee->can_employee_manage = $this->canManageUser(
                    $loggedInUser,
                    $employee
                );

                return $employee;
            }
        );

        return view('employees.index', [
            'employees' => $employees,
            'loggedInUser' => $loggedInUser,
            'isSuperAdmin' => $loggedInUser->hasRole('super_admin'),
        ]);
    }

    public function create(Request $request)
    {
        $loggedInUser = $request->user();
        $companyId = $loggedInUser->company_id;

        $allowedRoles = $this->getAllowedRoleNames($loggedInUser);

        $branches = Branch::query()
            ->where('company_id', $companyId);

        if (! empty($loggedInUser->branch_id)) {
            $branches->where('id', $loggedInUser->branch_id);
        } else {
            $branches->whereRaw('1 = 0');
        }

        return view('employees.form', [
            'employee' => new User(),

            'branches' => $branches
                ->orderBy('name')
                ->get(),

            'teams' => Team::query()
                ->where('company_id', $companyId)
                ->when(
                    ! empty($loggedInUser->team_id)
                    && $this->getUserRoleLevel($loggedInUser) <= $this->roleHierarchy['sales_manager'],
                    fn (Builder $query) => $query->where('id', $loggedInUser->team_id)
                )
                ->orderBy('name')
                ->get(),

            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $allowedRoles)
                ->orderByRaw("\n                    CASE name\n                        WHEN 'super_admin' THEN 1\n                        WHEN 'owner' THEN 2\n                        WHEN 'admin' THEN 3\n                        WHEN 'sales_manager' THEN 4\n                        WHEN 'team_leader' THEN 5\n                        WHEN 'employee' THEN 6\n                        ELSE 7\n                    END\n                ")
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $loggedInUser = $request->user();
        $companyId = $loggedInUser->company_id;
        $allowedRoles = $this->getAllowedRoleNames($loggedInUser);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'employee_code' => ['nullable', 'string', 'max:100', 'unique:users,employee_code'],
            'password' => ['required', 'string', 'min:8'],
            'branch_id' => ['nullable'],
            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')->where('company_id', $companyId),
            ],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
        ]);

        $role = $data['role'];
        unset($data['role']);

        $data['company_id'] = $companyId;
        $data['branch_id'] = $loggedInUser->branch_id;

        /*
         * Team leader / sales manager apni team se bahar employee create na kare.
         */
        if (
            ! empty($loggedInUser->team_id)
            && $this->getUserRoleLevel($loggedInUser) <= $this->roleHierarchy['sales_manager']
        ) {
            $data['team_id'] = $loggedInUser->team_id;
        }

        $data['password'] = Hash::make($data['password']);

        $employee = User::create($data);
        $employee->assignRole($role);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function edit(Request $request, User $employee)
    {
        $loggedInUser = $request->user();

        abort_unless(
            $this->canManageUser($loggedInUser, $employee),
            403,
            'You are not allowed to access this employee.'
        );

        $companyId = $employee->company_id;
        $allowedRoles = $this->getAllowedRoleNames($loggedInUser);

        $branches = Branch::query()
            ->where('company_id', $companyId);

        if (! $loggedInUser->hasRole('super_admin')) {
            if (! empty($loggedInUser->branch_id)) {
                $branches->where('id', $loggedInUser->branch_id);
            } elseif (! $loggedInUser->hasRole('owner')) {
                $branches->whereRaw('1 = 0');
            }
        }

        $teams = Team::query()
            ->where('company_id', $companyId);

        if (
            ! $loggedInUser->hasRole('super_admin')
            && ! empty($loggedInUser->team_id)
            && $this->getUserRoleLevel($loggedInUser) <= $this->roleHierarchy['sales_manager']
        ) {
            $teams->where('id', $loggedInUser->team_id);
        }

        return view('employees.form', [
            'employee' => $employee,
            'branches' => $branches->orderBy('name')->get(),
            'teams' => $teams->orderBy('name')->get(),
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $allowedRoles)
                ->orderByRaw("\n                    CASE name\n                        WHEN 'super_admin' THEN 1\n                        WHEN 'owner' THEN 2\n                        WHEN 'admin' THEN 3\n                        WHEN 'sales_manager' THEN 4\n                        WHEN 'team_leader' THEN 5\n                        WHEN 'employee' THEN 6\n                        ELSE 7\n                    END\n                ")
                ->get(),
        ]);
    }

    public function update(Request $request, User $employee)
    {
        $loggedInUser = $request->user();

        abort_unless(
            $this->canManageUser($loggedInUser, $employee),
            403,
            'You are not allowed to update this employee.'
        );

        $companyId = $employee->company_id;
        $allowedRoles = $this->getAllowedRoleNames($loggedInUser);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'employee_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('users', 'employee_code')->ignore($employee->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
            ],
            'team_id' => [
                'nullable',
                Rule::exists('teams', 'id')->where('company_id', $companyId),
            ],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = $data['role'];
        unset($data['role']);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        /*
         * Super admin target employee ki existing company ke andar branch/team change kar sakta hai.
         * Owner company ke andar branch/team manage kar sakta hai.
         * Baaki users apne scope ke bahar move nahi kar sakte.
         */
        if (! $loggedInUser->hasRole('super_admin') && ! $loggedInUser->hasRole('owner')) {
            $data['branch_id'] = $loggedInUser->branch_id;

            if (
                ! empty($loggedInUser->team_id)
                && $this->getUserRoleLevel($loggedInUser) <= $this->roleHierarchy['sales_manager']
            ) {
                $data['team_id'] = $loggedInUser->team_id;
            }
        }

        $data['is_active'] = $request->boolean('is_active');

        $employee->update($data);
        $employee->syncRoles([$role]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Request $request, User $employee)
    {
        $loggedInUser = $request->user();

        abort_unless(
            $this->canManageUser($loggedInUser, $employee),
            403,
            'You are not allowed to delete this employee.'
        );

        abort_if(
            (int) $employee->id === (int) $loggedInUser->id,
            422,
            'You cannot delete yourself.'
        );

        $employee->delete();

        return back()->with('success', 'Employee deleted successfully.');
    }

    public function impersonate(Request $request, User $employee)
    {
        $senior = $request->user();

        if ($request->session()->has('impersonator_id')) {
            return back()->with(
                'error',
                'You are already viewing another employee account.'
            );
        }

        abort_if(
            (int) $senior->id === (int) $employee->id,
            422,
            'You are already logged in to this account.'
        );

        abort_unless(
            $this->canImpersonate($senior, $employee),
            403,
            'You are not allowed to view this employee dashboard.'
        );

        $request->session()->put([
            'impersonator_id' => $senior->id,
            'impersonator_name' => $senior->name,
            'impersonator_company_id' => $senior->company_id,
            'impersonated_user_id' => $employee->id,
            'impersonated_user_name' => $employee->name,
        ]);

        Auth::login($employee);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'You are now viewing '.$employee->name.'\'s dashboard.'
            );
    }

    public function stopImpersonating(Request $request)
    {
        $seniorId = $request->session()->get('impersonator_id');

        abort_unless(
            $seniorId,
            403,
            'You are not impersonating any employee.'
        );

        $senior = User::find($seniorId);

        if (! $senior) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Original account could not be found.');
        }

        $request->session()->forget([
            'impersonator_id',
            'impersonator_name',
            'impersonator_company_id',
            'impersonated_user_id',
            'impersonated_user_name',
        ]);

        Auth::login($senior);
        $request->session()->regenerate();

        return redirect()
            ->route('employees.index')
            ->with('success', 'You are back to your account.');
    }

    /**
     * Apply employee visibility to list query.
     */
    private function applyEmployeeVisibilityScope(
        Builder $query,
        User $viewer
    ): void {
        /* Super Admin: absolutely all users, all companies/branches/teams. */
        if ($viewer->hasRole('super_admin')) {
            return;
        }

        $viewerLevel = $this->getUserRoleLevel($viewer);

        /* Employee only sees own record. */
        if ($viewerLevel <= $this->roleHierarchy['employee']) {
            $query->whereKey($viewer->id);
            return;
        }

        /* Every non-super-admin is restricted to own company. */
        $query->where('company_id', $viewer->company_id);

        /* Only LOWER roles are visible. Same/higher roles are hidden. */
        $sameOrHigherRoles = collect($this->roleHierarchy)
            ->filter(fn (int $level) => $level >= $viewerLevel)
            ->keys()
            ->all();

        if (! empty($sameOrHigherRoles)) {
            $query->whereDoesntHave(
                'roles',
                fn (Builder $roleQuery) => $roleQuery->whereIn('name', $sameOrHigherRoles)
            );
        }

        /* Owner: whole company, all branches and all teams. */
        if ($viewer->hasRole('owner')) {
            return;
        }

        /* Admin and below: branch restriction when branch is assigned. */
        if (! empty($viewer->branch_id)) {
            $query->where('branch_id', $viewer->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        /* Sales Manager / Team Leader: team restriction when team is assigned. */
        if (
            $viewerLevel <= $this->roleHierarchy['sales_manager']
            && ! empty($viewer->team_id)
        ) {
            $query->where('team_id', $viewer->team_id);
        }

        /* Team leader without a team should not see unrelated employees. */
        if (
            $viewer->hasRole('team_leader')
            && empty($viewer->team_id)
        ) {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * Dashboard impersonation follows the same company/branch/team hierarchy.
     */
    private function canImpersonate(User $senior, User $employee): bool
    {
        if ((int) $senior->id === (int) $employee->id) {
            return false;
        }

        /* Super Admin can open any employee account. */
        if ($senior->hasRole('super_admin')) {
            return true;
        }

        if (! $this->isUserInsideViewerScope($senior, $employee)) {
            return false;
        }

        $seniorLevel = $this->getUserRoleLevel($senior);
        $employeeLevel = $this->getUserRoleLevel($employee);

        /* Target must be a strictly lower role. */
        return $employeeLevel < $seniorLevel;
    }

    /**
     * Edit/Delete rules.
     */
    private function canManageUser(User $loggedInUser, User $targetUser): bool
    {
        /* Super Admin can manage every user. */
        if ($loggedInUser->hasRole('super_admin')) {
            return true;
        }

        if ((int) $loggedInUser->id === (int) $targetUser->id) {
            return true;
        }

        if (! $this->isUserInsideViewerScope($loggedInUser, $targetUser)) {
            return false;
        }

        $loggedInLevel = $this->getUserRoleLevel($loggedInUser);
        $targetLevel = $this->getUserRoleLevel($targetUser);

        return $targetLevel < $loggedInLevel;
    }

    /**
     * Same organizational scope check.
     */
    private function isUserInsideViewerScope(User $viewer, User $target): bool
    {
        if ($viewer->hasRole('super_admin')) {
            return true;
        }

        if (
            empty($viewer->company_id)
            || (int) $viewer->company_id !== (int) $target->company_id
        ) {
            return false;
        }

        /* Owner gets all branches and teams in own company. */
        if ($viewer->hasRole('owner')) {
            return true;
        }

        /* Admin and below must match branch. */
        if (! $this->sameNullableId($viewer->branch_id, $target->branch_id)) {
            return false;
        }

        $viewerLevel = $this->getUserRoleLevel($viewer);

        /* Sales manager/team leader are team scoped when team is assigned. */
        if ($viewerLevel <= $this->roleHierarchy['sales_manager']) {
            if (! empty($viewer->team_id)) {
                return (int) $viewer->team_id === (int) $target->team_id;
            }

            if ($viewer->hasRole('team_leader')) {
                return false;
            }
        }

        return true;
    }

    private function sameNullableId($first, $second): bool
    {
        if (empty($first) && empty($second)) {
            return true;
        }

        if (empty($first) || empty($second)) {
            return false;
        }

        return (int) $first === (int) $second;
    }

    private function getUserRoleLevel(User $user): int
    {
        $highestLevel = 0;

        foreach ($user->getRoleNames() as $roleName) {
            $level = $this->roleHierarchy[$roleName] ?? 0;
            $highestLevel = max($highestLevel, $level);
        }

        return $highestLevel;
    }

    /**
     * User can create/assign only own level or lower roles.
     */
    private function getAllowedRoleNames(User $user): array
    {
        $loggedInLevel = $this->getUserRoleLevel($user);

        return collect($this->roleHierarchy)
            ->filter(fn (int $level) => $level <= $loggedInLevel)
            ->keys()
            ->values()
            ->all();
    }
}