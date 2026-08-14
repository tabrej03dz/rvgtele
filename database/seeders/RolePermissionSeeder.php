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
        /*
        |--------------------------------------------------------------------------
        | Clear Permission Cache
        |--------------------------------------------------------------------------
        */

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Create Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            'dashboard.view',
            'access-control.manage',

            'leads.view',
            'leads.create',
            'leads.edit',
            'leads.delete',
            'leads.assign',

            'calls.manage',
            'followups.manage',
            'pipeline.manage',

            'employees.manage',
            'campaigns.manage',
            'products.manage',
            'customers.manage',
            'tasks.manage',
            'orders.manage',
            'payments.manage',

            'reports.view',
            'settings.manage',
            'export.data',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Roles
        |--------------------------------------------------------------------------
        */

        $roles = [
            'super_admin',
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
                'name'       => $role,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Super Admin
        |--------------------------------------------------------------------------
        */

        Role::findByName('super_admin', 'web')
            ->syncPermissions(Permission::where('guard_name', 'web')->get());

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Company Owner
        |--------------------------------------------------------------------------
        */

        Role::findByName('company_owner', 'web')
            ->syncPermissions(Permission::where('guard_name', 'web')->get());

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Sales Manager
        |--------------------------------------------------------------------------
        */

        Role::findByName('sales_manager', 'web')
            ->syncPermissions(
                Permission::where('guard_name', 'web')
                    ->whereNotIn('name', [
                        'settings.manage',
                        'access-control.manage',
                    ])
                    ->get()
            );

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Team Leader
        |--------------------------------------------------------------------------
        */

        Role::findByName('team_leader', 'web')
            ->syncPermissions([
                'dashboard.view',
                'leads.view',
                'leads.create',
                'leads.edit',
                'leads.assign',
                'calls.manage',
                'followups.manage',
                'pipeline.manage',
                'tasks.manage',
                'reports.view',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Telecaller
        |--------------------------------------------------------------------------
        */

        Role::findByName('telecaller', 'web')
            ->syncPermissions([
                'dashboard.view',
                'leads.view',
                'leads.edit',
                'calls.manage',
                'followups.manage',
                'tasks.manage',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Sales Executive
        |--------------------------------------------------------------------------
        */

        Role::findByName('sales_executive', 'web')
            ->syncPermissions([
                'dashboard.view',
                'leads.view',
                'leads.edit',
                'pipeline.manage',
                'customers.manage',
                'tasks.manage',
                'orders.manage',
                'payments.manage',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Accounts User
        |--------------------------------------------------------------------------
        */

        Role::findByName('accounts_user', 'web')
            ->syncPermissions([
                'dashboard.view',
                'customers.manage',
                'orders.manage',
                'payments.manage',
                'reports.view',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Clear Permission Cache Again
        |--------------------------------------------------------------------------
        */

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}