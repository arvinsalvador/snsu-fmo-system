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
        'manage_master_data',
        'manage_locations',
        'manage_work_order_settings',
        'create_work_orders',
        'view_work_orders',
        'update_work_orders',
        'delete_work_orders',
        'manage_work_orders',
        'approve_work_orders',
        'reject_work_orders',
        'view_work_order_approvals',
        'assign_work_orders',
        'reassign_work_orders',
        'view_assignments',
        'view_assignment_recommendations',
        'view_staff_workload',
        'update_work_order_progress',
        'view_work_order_updates',
        'create_work_order_updates',
        'manage_work_order_updates',
        'evaluate_work_orders',
        'view_evaluations',
        'manage_inventory',
        'manage_assets',
        'manage_maintenance',
        'view_reports',
        'manage_notifications',
        'view_followups',
        'create_followups',
        'view_notifications',
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

            if (in_array($roleName, ['FMO Head', 'Campus Director', 'Director for Instruction'], true)) {
                $role->syncPermissions([
                    'view_dashboard',
                    'manage_staff_profiles',
                    'manage_skills',
                    'manage_master_data',
                    'manage_locations',
                    'manage_work_order_settings',
                    'create_work_orders',
                    'view_work_orders',
                    'update_work_orders',
                    'delete_work_orders',
                    'manage_work_orders',
                    'approve_work_orders',
                    'reject_work_orders',
                    'view_work_order_approvals',
                    'assign_work_orders',
                    'reassign_work_orders',
                    'view_assignments',
                    'view_assignment_recommendations',
                    'view_staff_workload',
                    'view_work_order_updates',
                    'create_work_order_updates',
                    'manage_work_order_updates',
                    'view_followups',
                    'create_followups',
                    'view_notifications',
                    'view_evaluations',
                    'view_reports',
                ]);
            }

            if ($roleName === 'FMO Staff') {
                $role->syncPermissions([
                    'view_dashboard',
                    'view_work_orders',
                    'update_work_order_progress',
                    'view_work_order_updates',
                    'create_work_order_updates',
                    'view_followups',
                    'create_followups',
                    'view_notifications',
                    'view_evaluations',
                    'sync_mobile_data',
                ]);
            }

            if (in_array($roleName, ['Faculty', 'Admin/Staff', 'Student'], true)) {
                $role->syncPermissions([
                    'view_dashboard',
                    'create_work_orders',
                    'view_work_orders',
                    'view_work_order_updates',
                    'view_followups',
                    'create_followups',
                    'view_notifications',
                    'evaluate_work_orders',
                    'view_evaluations',
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
