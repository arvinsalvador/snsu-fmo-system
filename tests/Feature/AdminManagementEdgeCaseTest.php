<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\StaffProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SkillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminManagementEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_cannot_remove_own_super_admin_role(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->from(route('admin.users.edit', $admin))->put(route('admin.users.update', $admin), [
            'first_name' => $admin->first_name, 'last_name' => $admin->last_name, 'email' => $admin->email,
            'roles_present' => true, 'roles' => [], 'is_active' => true,
        ])->assertRedirect(route('admin.users.edit', $admin))->assertSessionHasErrors('roles');

        $this->assertTrue($admin->refresh()->hasRole('Super Admin'));
    }

    public function test_admin_forms_can_clear_role_permissions_and_staff_skills(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');
        $role = Role::create(['name' => 'Temporary Manager', 'guard_name' => 'web']);
        $role->givePermissionTo('view_dashboard');

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => $role->name, 'permissions_present' => true,
        ])->assertRedirect(route('admin.roles.show', $role));
        $this->assertCount(0, $role->refresh()->permissions);

        $staffUser = $this->userWithRole('FMO Staff');
        $profile = StaffProfile::factory()->create(['user_id' => $staffUser->id]);
        $profile->skills()->attach(Skill::query()->firstOrFail());
        $this->actingAs($admin)->put(route('admin.staff.skills.update', $profile), ['skills_present' => true])
            ->assertRedirect(route('admin.staff.show', $profile));
        $this->assertCount(0, $profile->skills()->get());
    }

    public function test_admin_can_remove_all_roles_from_another_user(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');
        $user = $this->userWithRole('Faculty');

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'first_name' => $user->first_name, 'last_name' => $user->last_name, 'email' => $user->email,
            'roles_present' => true, 'is_active' => true,
        ])->assertRedirect(route('admin.users.show', $user));

        $this->assertCount(0, $user->refresh()->roles);
    }

    private function seedFoundation(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, SkillSeeder::class]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
