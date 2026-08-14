<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',

            'companies.view',
            'companies.create',
            'companies.update',
            'companies.delete',
            'companies.view-business',

            'activity-logs.view',

            'leads.view',
            'leads.create',
            'leads.update',
            'leads.delete',
            'leads.import',
            'leads.assign',
            'leads.notes.create',
            'leads.labels.manage',

            'calls.view',
            'calls.create',

            'followups.view',
            'followups.complete',
            'followups.delete',

            'pipeline.view',
            'pipeline.move',

            'access-control.view',
            'access-control.roles.create',
            'access-control.roles.delete',
            'access-control.permissions.create',
            'access-control.permissions.delete',
            'access-control.user-permissions.assign',
            'access-control.role-permissions.assign',

            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',
            'employees.impersonate',

            'branches.view',
            'branches.create',
            'branches.update',
            'branches.delete',

            'teams.view',
            'teams.create',
            'teams.update',
            'teams.delete',

            'campaigns.view',
            'campaigns.create',
            'campaigns.update',
            'campaigns.delete',

            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',

            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',

            'orders.view',
            'orders.create',
            'orders.update',
            'orders.delete',

            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',

            'lead-sources.view',
            'lead-sources.create',
            'lead-sources.update',
            'lead-sources.delete',

            'lead-statuses.view',
            'lead-statuses.create',
            'lead-statuses.update',
            'lead-statuses.delete',

            'call-dispositions.view',
            'call-dispositions.create',
            'call-dispositions.update',
            'call-dispositions.delete',

            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'super_admin',
            'owner',
            'admin',
            'company_owner',
            'sales_manager',
            'team_leader',
            'telecaller',
            'sales_executive',
            'field_sales_executive',
            'quality_analyst',
            'customer_support',
            'accounts_user',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        // Super Admin always receives every permission. Gate::before() also
        // guarantees full access even when a newly-created permission has not
        // yet been explicitly attached to the role.
        Role::findByName('super_admin', 'web')
            ->syncPermissions(Permission::where('guard_name', 'web')->get());

        // Baseline permissions for existing system roles. These calls add the
        // baseline without removing any custom permission already assigned.
        $baseline = [
            'owner' => array_values(array_filter($permissions, fn (string $p) =>
                ! str_starts_with($p, 'companies.')
                && ! in_array($p, [
                    'access-control.roles.create',
                    'access-control.roles.delete',
                    'access-control.permissions.create',
                    'access-control.permissions.delete',
                    'access-control.role-permissions.assign',
                ], true)
            )),
            'company_owner' => array_values(array_filter($permissions, fn (string $p) =>
                ! str_starts_with($p, 'companies.')
                && ! in_array($p, [
                    'access-control.roles.create',
                    'access-control.roles.delete',
                    'access-control.permissions.create',
                    'access-control.permissions.delete',
                    'access-control.role-permissions.assign',
                ], true)
            )),
            'admin' => array_values(array_filter($permissions, fn (string $p) =>
                ! str_starts_with($p, 'companies.')
                && ! str_starts_with($p, 'access-control.')
            )),
            'sales_manager' => [
                'dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.import', 'leads.assign',
                'leads.notes.create', 'leads.labels.manage', 'calls.view', 'calls.create', 'followups.view',
                'followups.complete', 'pipeline.view', 'pipeline.move', 'employees.view', 'employees.create',
                'employees.update', 'teams.view', 'campaigns.view', 'customers.view', 'customers.create',
                'customers.update', 'tasks.view', 'tasks.create', 'tasks.update', 'orders.view', 'reports.view',
            ],
            'team_leader' => [
                'dashboard.view', 'leads.view', 'leads.create', 'leads.update', 'leads.assign', 'leads.notes.create',
                'leads.labels.manage', 'calls.view', 'calls.create', 'followups.view', 'followups.complete',
                'pipeline.view', 'pipeline.move', 'employees.view', 'tasks.view', 'tasks.create', 'tasks.update',
                'reports.view',
            ],
            'telecaller' => [
                'dashboard.view', 'leads.view', 'leads.update', 'leads.notes.create', 'calls.view', 'calls.create',
                'followups.view', 'followups.complete', 'tasks.view', 'tasks.update',
            ],
            'sales_executive' => [
                'dashboard.view', 'leads.view', 'leads.update', 'leads.notes.create', 'calls.view', 'calls.create',
                'followups.view', 'pipeline.view', 'customers.view', 'customers.create', 'customers.update',
                'tasks.view', 'tasks.create', 'tasks.update', 'orders.view', 'orders.create', 'orders.update',
                'payments.view',
            ],
            'accounts_user' => [
                'dashboard.view', 'customers.view', 'orders.view', 'payments.view', 'payments.create',
                'payments.update', 'reports.view',
            ],
        ];

        foreach ($baseline as $roleName => $rolePermissions) {
            $role = Role::findByName($roleName, 'web');
            $role->givePermissionTo($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
