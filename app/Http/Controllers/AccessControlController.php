<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $this->ensureCanManageAccess($actor);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions:id,name,guard_name')
            ->orderBy('name')
            ->get();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $usersQuery = User::query()
            ->with(['roles:id,name,guard_name', 'permissions:id,name,guard_name'])
            ->orderBy('name');

        if (!$actor->hasRole('super_admin')) {
            $usersQuery->where('company_id', $actor->company_id)
                ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'super_admin'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $users = $usersQuery->paginate(15)->withQueryString();

        return view('access-control.index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'users' => $users,
            'canManageDefinitions' => $actor->hasRole('super_admin'),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureSuperAdmin($actor);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
        ]);

        Role::create([
            'name' => strtolower(trim($data['name'])),
            'guard_name' => 'web',
        ]);

        $this->forgetPermissionCache();

        return back()->with('success', 'New role created successfully.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureSuperAdmin($actor);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'web'),
            ],
        ]);

        Permission::create([
            'name' => strtolower(trim($data['name'])),
            'guard_name' => 'web',
        ]);

        $this->forgetPermissionCache();

        return back()->with('success', 'New permission created successfully.');
    }

    public function syncRolePermissions(Request $request, Role $role): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureSuperAdmin($actor);

        abort_unless($role->guard_name === 'web', 404);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where('guard_name', 'web'),
            ],
        ]);

        if ($role->name === 'super_admin') {
            $role->syncPermissions(
                Permission::query()->where('guard_name', 'web')->get()
            );
        } else {
            $role->syncPermissions($data['permissions'] ?? []);
        }

        $this->forgetPermissionCache();

        return back()->with(
            'success',
            $role->name === 'super_admin'
                ? 'Super Admin has been kept with all permissions.'
                : 'Role permissions updated successfully.'
        );
    }

    public function syncUserAccess(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureCanManageAccess($actor);
        $this->ensureUserIsManageable($actor, $user);

        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'integer',
                Rule::exists('roles', 'id')->where('guard_name', 'web'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where('guard_name', 'web'),
            ],
        ]);

        $roleIds = collect($data['roles'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $permissionIds = collect($data['permissions'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $roleIds)
            ->get();

        if (!$actor->hasRole('super_admin') && $roles->contains('name', 'super_admin')) {
            abort(403, 'Only Super Admin can assign the Super Admin role.');
        }

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $permissionIds)
            ->get();

        $user->syncRoles($roles);
        $user->syncPermissions($permissions);

        if ($user->hasRole('super_admin')) {
            $superAdminRole = Role::findByName('super_admin', 'web');
            $superAdminRole->syncPermissions(
                Permission::query()->where('guard_name', 'web')->get()
            );
        }

        $this->forgetPermissionCache();

        return back()->with('success', "Access updated for {$user->name}.");
    }

    private function ensureCanManageAccess(User $user): void
    {
        abort_unless(
            $user->hasRole('super_admin') || $user->can('access-control.manage'),
            403,
            'You do not have permission to manage roles and permissions.'
        );
    }

    private function ensureSuperAdmin(User $user): void
    {
        abort_unless(
            $user->hasRole('super_admin'),
            403,
            'Only Super Admin can create roles/permissions or change role definitions.'
        );
    }

    private function ensureUserIsManageable(User $actor, User $target): void
    {
        if ($actor->hasRole('super_admin')) {
            return;
        }

        abort_if(
            !$actor->company_id || (int) $actor->company_id !== (int) $target->company_id,
            403,
            'You can manage users of your own company only.'
        );

        abort_if(
            $target->hasRole('super_admin'),
            403,
            'Super Admin access can be managed by Super Admin only.'
        );
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}