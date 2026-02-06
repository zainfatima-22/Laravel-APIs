<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for the sanctum guard
        $permissions = [
            'ticket_view',
            'ticket_create',
            'ticket_update',
            'ticket_delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum']
            );
        }

        // Create user role for sanctum guard
        $userRole = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'sanctum']
        );

        // Assign all permissions to user role
        $userRole->syncPermissions($permissions);

        // Create admin role for sanctum guard
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'sanctum']
        );

        // Assign all permissions to admin role
        $adminRole->syncPermissions($permissions);
    }
}
