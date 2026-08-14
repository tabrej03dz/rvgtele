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

        $this->authorizePermission($actor, 'access-control.view');

        $guard = (string) $request->get('guard', 'web');

        if (! in_array($guard, ['web'], true)) {
            $guard = 'web';
        }

        $roles = Role::query()
            ->where('guard_name', $guard)
            ->with([
                'permissions' => function ($query) {
                    $query->select(
                        'permissions.id',
                        'permissions.name',
                        'permissions.guard_name'
                    );
                },
            ])
            ->orderBy('name')
            ->get();

        $permissionsQuery = Permission::query()
            ->where('guard_name', $guard);

        if (! $actor->hasRole('super_admin')) {
            $permissionsQuery->whereIn(
                'id',
                $actor->getAllPermissions()->pluck('id')
            );
        }

        $permissions = $permissionsQuery
            ->orderBy('name')
            ->get();

        $usersQuery = User::query()
            ->with([
                'roles:id,name,guard_name',
                'permissions:id,name,guard_name',
                'company:id,name',
            ])
            ->orderBy('name');

        /*
        |--------------------------------------------------------------------------
        | User Visibility
        |--------------------------------------------------------------------------
        |
        | Super Admin = all users
        | Other access-control managers = own company users only
        |
        */

        if (! $actor->hasRole('super_admin')) {
            $usersQuery
                ->where('company_id', $actor->company_id)
                ->whereDoesntHave('roles', function ($query) {
                    $query->where('name', 'super_admin');
                });
        }

        $users = $usersQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Existing Role Permissions
        |--------------------------------------------------------------------------
        |
        | Role select karte hi currently assigned permissions checked dikhenge.
        |
        */

        $rolePermissionMap = $roles->mapWithKeys(function (Role $role) {
            return [
                (string) $role->id => $role->permissions
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Existing Direct User Permissions
        |--------------------------------------------------------------------------
        |
        | Sirf direct permissions yaha load hongi.
        | Role se inherited permissions alag role ke through manage hongi.
        |
        */

        $userPermissionMap = $users->mapWithKeys(function (User $user) {
            return [
                (string) $user->id => $user->permissions
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
            ];
        });

        return view('access-control.index', [
            'guard' => $guard,
            'roles' => $roles,
            'permissions' => $permissions,
            'users' => $users,
            'rolePermissionMap' => $rolePermissionMap,
            'userPermissionMap' => $userPermissionMap,
            'canManageDefinitions' => $actor->can('access-control.roles.create') || $actor->can('access-control.permissions.create'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Role
    |--------------------------------------------------------------------------
    */

    public function storeRole(Request $request): RedirectResponse
    {
        $actor = $request->user();

        $this->authorizePermission($actor, 'access-control.roles.create');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._ -]+$/',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web'),
            ],

            'guard_name' => [
                'nullable',
                Rule::in(['web']),
            ],
        ]);

        $name = strtolower(trim($data['name']));

        $name = preg_replace(
            '/\s+/',
            '_',
            $name
        );

        Role::create([
            'name' => $name,
            'guard_name' => 'web',
        ]);

        $this->forgetPermissionCache();

        return back()->with(
            'success',
            'Role created successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Permission
    |--------------------------------------------------------------------------
    */

    public function storePermission(Request $request): RedirectResponse
    {
        $actor = $request->user();

        $this->authorizePermission($actor, 'access-control.permissions.create');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                'regex:/^[a-zA-Z0-9._ -]+$/',

                Rule::unique('permissions', 'name')
                    ->where('guard_name', 'web'),
            ],

            'guard_name' => [
                'nullable',
                Rule::in(['web']),
            ],
        ]);

        $name = strtolower(trim($data['name']));

        $name = preg_replace(
            '/\s+/',
            '_',
            $name
        );

        $permission = Permission::create([
            'name' => $name,
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Always Give New Permission To Super Admin
        |--------------------------------------------------------------------------
        */

        $superAdminRole = Role::query()
            ->where('name', 'super_admin')
            ->where('guard_name', 'web')
            ->first();

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permission);
        }

        $this->forgetPermissionCache();

        return back()->with(
            'success',
            'Permission created successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Assign / Sync Permissions
    |--------------------------------------------------------------------------
    |
    | User select = direct permissions sync
    |
    | Role select = role permissions sync
    |
    | Dono select = dono par same selected permissions save
    |
    */

    public function assignPermissions(Request $request): RedirectResponse
    {
        $actor = $request->user();

        $data = $request->validate([
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'role_id' => [
                'nullable',
                'integer',

                Rule::exists('roles', 'id')
                    ->where('guard_name', 'web'),
            ],

            'guard_name' => [
                'required',
                Rule::in(['web']),
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'integer',

                Rule::exists('permissions', 'id')
                    ->where('guard_name', 'web'),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Target Required
        |--------------------------------------------------------------------------
        */

        if (
            empty($data['user_id']) &&
            empty($data['role_id'])
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please select a user or role first.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Selected Permissions
        |--------------------------------------------------------------------------
        */

        $permissionIds = collect(
            $data['permissions'] ?? []
        )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $permissionModels = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $permissionIds)
            ->get();

        if (! $actor->hasRole('super_admin')) {
            $actorPermissionIds = $actor->getAllPermissions()
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            abort_if(
                $permissionModels->pluck('id')->diff($actorPermissionIds)->isNotEmpty(),
                403,
                'You can only assign permissions that you already have.'
            );
        }

        $messages = [];

        /*
        |--------------------------------------------------------------------------
        | Direct User Permission
        |--------------------------------------------------------------------------
        */

        if (! empty($data['user_id'])) {

            $this->authorizePermission(
                $actor,
                'access-control.user-permissions.assign'
            );

            $targetUser = User::query()
                ->findOrFail(
                    (int) $data['user_id']
                );

            $this->ensureUserIsManageable(
                $actor,
                $targetUser
            );

            $targetUser->syncPermissions(
                $permissionModels
            );

            $messages[] = "user {$targetUser->name}";
        }

        /*
        |--------------------------------------------------------------------------
        | Role Permission
        |--------------------------------------------------------------------------
        |
        | Current Spatie roles global hain.
        | Isliye role definition sirf Super Admin change karega.
        |
        */

        if (! empty($data['role_id'])) {

            $this->authorizePermission(
                $actor,
                'access-control.role-permissions.assign'
            );

            $role = Role::query()
                ->where(
                    'guard_name',
                    'web'
                )
                ->findOrFail(
                    (int) $data['role_id']
                );

            /*
            |--------------------------------------------------------------------------
            | Super Admin Cannot Lose Permissions
            |--------------------------------------------------------------------------
            */

            if ($role->name === 'super_admin') {

                $role->syncPermissions(
                    Permission::query()
                        ->where(
                            'guard_name',
                            'web'
                        )
                        ->get()
                );

            } else {

                $role->syncPermissions(
                    $permissionModels
                );
            }

            $messages[] = "role {$role->name}";
        }

        $this->forgetPermissionCache();

        return back()->with(
            'success',
            'Permissions updated successfully for '
            .implode(' and ', $messages)
            .'.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Permission
    |--------------------------------------------------------------------------
    */

    public function destroyPermission(
        Request $request,
        Permission $permission
    ): RedirectResponse {

        $actor = $request->user();

        $this->authorizePermission($actor, 'access-control.permissions.delete');

        abort_unless(
            $permission->guard_name === 'web',
            404
        );

        /*
        |--------------------------------------------------------------------------
        | Protected Permission
        |--------------------------------------------------------------------------
        */

        if (
            $permission->name ===
            'access-control.view'
        ) {
            return back()->with(
                'error',
                'access-control.view is a protected permission and cannot be deleted.'
            );
        }

        $permissionName =
            $permission->name;

        $permission->delete();

        $this->forgetPermissionCache();

        return back()->with(
            'success',
            "Permission {$permissionName} deleted successfully."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Role
    |--------------------------------------------------------------------------
    */

    public function destroyRole(
        Request $request,
        Role $role
    ): RedirectResponse {

        $actor = $request->user();

        $this->authorizePermission($actor, 'access-control.roles.delete');

        abort_unless(
            $role->guard_name === 'web',
            404
        );

        /*
        |--------------------------------------------------------------------------
        | Core Roles Protected
        |--------------------------------------------------------------------------
        */

        $protectedRoles = [
            'super_admin',
            'company_owner',
            'owner',
            'admin',
            'team_leader',
            'employee',
        ];

        if (
            in_array(
                $role->name,
                $protectedRoles,
                true
            )
        ) {
            return back()->with(
                'error',
                "{$role->name} is a protected system role and cannot be deleted."
            );
        }

        $roleName = $role->name;

        $role->delete();

        $this->forgetPermissionCache();

        return back()->with(
            'success',
            "Role {$roleName} deleted successfully."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    private function authorizePermission(User $user, string $permission): void
    {
        abort_unless(
            $user->can($permission),
            403,
            'You do not have permission to perform this action.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Manageable User
    |--------------------------------------------------------------------------
    */

    private function ensureUserIsManageable(
        User $actor,
        User $target
    ): void {

        if (
            $actor->hasRole(
                'super_admin'
            )
        ) {
            return;
        }

        abort_if(
            ! $actor->company_id
            ||
            (int) $actor->company_id
            !==
            (int) $target->company_id,
            403,
            'You can manage users of your own company only.'
        );

        abort_if(
            $target->hasRole(
                'super_admin'
            ),
            403,
            'Super Admin access can only be managed by Super Admin.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Cache
    |--------------------------------------------------------------------------
    */

    private function forgetPermissionCache(): void
    {
        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }
}