<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WhatsappTemplatePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'whatsapp-template.view-own',
            'whatsapp-template.create',
            'whatsapp-template.edit-own',
            'whatsapp-template.delete-own',

            'whatsapp-template.view-all',
            'whatsapp-template.edit-all',
            'whatsapp-template.delete-all',
            'whatsapp-template.create-global',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::where('name', 'super-admin')
            ->orWhere('name', 'super_admin')
            ->orWhere('name', 'Super Admin')
            ->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo(
                Permission::whereIn('name', $permissions)->get()
            );
        }
    }
}
