<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundational_roles_and_permissions_are_seeded(): void
    {
        $this->seed();

        $expectedRoles = [
            'Super Admin',
            'FMO Head',
            'Campus Director',
            'Director for Instruction',
            'FMO Staff',
            'Faculty',
            'Admin/Staff',
            'Student',
        ];

        foreach ($expectedRoles as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role, 'guard_name' => 'web']);
        }

        $this->assertDatabaseHas('permissions', ['name' => 'manage_users', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'sync_mobile_data', 'guard_name' => 'web']);

        $superAdmin = Role::findByName('Super Admin');

        $this->assertTrue($superAdmin->hasPermissionTo('manage_users'));
        $this->assertSame(Permission::count(), $superAdmin->permissions()->count());
    }
}
