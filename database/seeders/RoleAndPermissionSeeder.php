<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $roles = [
        'Super Admin',
        'FMO Head',
        'Campus Director',
        'Director for Instruction',
        'FMO Staff',
        'Faculty',
        'Admin/Staff',
        'Student',
    ];

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'view_dashboard',
        'manage_users',
        'manage_roles',
        'manage_permissions',
        'manage_staff_profiles',
        'manage_skills',
        'manage_locations',
        'create_work_orders',
        'view_work_orders',
        'approve_work_orders',
        'assign_work_orders',
        'update_work_order_progress',
        'evaluate_work_orders',
        'manage_inventory',
        'manage_assets',
        'manage_maintenance',
        'view_reports',
        'manage_notifications',
        'sync_mobile_data',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $permissions = Permission::query()->whereIn('name', $this->permissions)->get();

        foreach ($this->roles as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');

            if ($roleName === 'Super Admin') {
                $role->syncPermissions($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
