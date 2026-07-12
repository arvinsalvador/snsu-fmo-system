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
        'view_inventory',
        'manage_inventory',
        'adjust_inventory',
        'export_inventory',
        'issue_materials',
        'view_assets',
        'manage_assets',
        'export_assets',
        'view_maintenance_records',
        'manage_maintenance_records',
        'view_maintenance_schedules',
        'manage_maintenance_schedules',
        'complete_maintenance_schedules',
        'export_maintenance_records',
        'export_maintenance_schedules',
        'view_maintenance_reviews',
        'review_maintenance_records',
        'approve_maintenance_records',
        'request_maintenance_corrections',
        'correct_maintenance_records',
        'reject_maintenance_records',
        'reopen_maintenance_records',
        'manage_maintenance',
        'view_reports',
        'view_work_order_reports',
        'view_asset_reports',
        'view_maintenance_reports',
        'view_inventory_reports',
        'view_staff_reports',
        'export_reports',
        'view_report_templates',
        'manage_report_templates',
        'generate_reports',
        'view_generated_reports',
        'download_generated_reports',
        'delete_generated_reports',
        'view_report_schedules',
        'manage_report_schedules',
        'run_report_schedules',
        'view_report_delivery_logs',
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
                    'view_inventory',
                    'manage_inventory',
                    'adjust_inventory',
                    'export_inventory',
                    'issue_materials',
                    'view_assets',
                    'manage_assets',
                    'export_assets',
                    'view_maintenance_records',
                    'manage_maintenance_records',
                    'view_maintenance_schedules',
                    'manage_maintenance_schedules',
                    'complete_maintenance_schedules',
                    'export_maintenance_records',
                    'export_maintenance_schedules',
                    'view_maintenance_reviews',
                    'review_maintenance_records',
                    'approve_maintenance_records',
                    'request_maintenance_corrections',
                    'correct_maintenance_records',
                    'reject_maintenance_records',
                    'manage_maintenance',
                    'view_reports',
                    'view_work_order_reports',
                    'view_asset_reports',
                    'view_maintenance_reports',
                    'view_inventory_reports',
                    'view_staff_reports',
                    'export_reports',
                    'view_report_templates',
                    'manage_report_templates',
                    'generate_reports',
                    'view_generated_reports',
                    'download_generated_reports',
                    'view_report_schedules',
                    'manage_report_schedules',
                    'run_report_schedules',
                    'view_report_delivery_logs',
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
                    'view_inventory',
                    'view_assets',
                    'view_maintenance_records',
                    'view_maintenance_schedules',
                    'correct_maintenance_records',
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
