<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'sanctum'
        ]);

        $company = Role::firstOrCreate([
            'name' => 'company',
            'guard_name' => 'sanctum'
        ]);

        $user = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'sanctum'
        ]);

        Permission::firstOrCreate([
            'name' => 'manage companies',
            'guard_name' => 'sanctum'
        ]);

        Permission::firstOrCreate([
            'name' => 'create jobs',
            'guard_name' => 'sanctum'
        ]);

        Permission::firstOrCreate([
            'name' => 'edit jobs',
            'guard_name' => 'sanctum'
        ]);

        Permission::firstOrCreate([
            'name' => 'delete jobs',
            'guard_name' => 'sanctum'
        ]);

        Permission::firstOrCreate([
            'name' => 'apply jobs',
            'guard_name' => 'sanctum'
        ]);

        // ربط الصلاحيات بالأدوار
        $admin->syncPermissions(Permission::all());

        $company->syncPermissions([
            'create jobs',
            'edit jobs',
            'delete jobs'
        ]);

        $user->syncPermissions([
            'apply jobs'
        ]);
    }
}
