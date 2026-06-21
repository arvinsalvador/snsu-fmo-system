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

class AdminManagementWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_every_admin_management_area(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');

        foreach (['admin.users.index', 'admin.staff.index', 'admin.skills.index', 'admin.roles.index', 'admin.permissions.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertSee('Users')->assertSee('Staff Profiles')->assertSee('Roles')->assertSee('Permissions');
    }

    public function test_fmo_head_can_manage_staff_and_skills_but_not_users_or_access_control(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');

        $this->actingAs($head)->get(route('admin.staff.index'))->assertOk()->assertSee('Staff profiles');
        $this->actingAs($head)->get(route('admin.skills.index'))->assertOk()->assertSee('Skills catalog');
        $this->actingAs($head)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($head)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($head)->get(route('admin.permissions.index'))->assertForbidden();

        $this->actingAs($head)->get(route('admin.staff.index'))
            ->assertDontSee('href="'.route('admin.users.index').'"', false)
            ->assertDontSee('href="'.route('admin.roles.index').'"', false);
    }

    public function test_super_admin_can_create_update_assign_roles_and_deactivate_user(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria@example.com',
            'password' => 'password', 'password_confirmation' => 'password', 'roles' => ['Faculty'], 'is_active' => true,
        ])->assertRedirect();

        $user = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Faculty'));

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'first_name' => 'Maria', 'last_name' => 'Reyes', 'email' => 'maria@example.com',
            'roles' => ['Admin/Staff'], 'is_active' => true,
        ])->assertRedirect(route('admin.users.show', $user));
        $this->assertTrue($user->refresh()->hasRole('Admin/Staff'));
        $this->assertSame('Maria Reyes', $user->name);

        $this->actingAs($admin)->patch(route('admin.users.deactivate', $user))->assertRedirect();
        $this->assertFalse($user->refresh()->is_active);
    }

    public function test_super_admin_cannot_deactivate_own_account(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->from(route('admin.users.show', $admin))
            ->patch(route('admin.users.deactivate', $admin))
            ->assertRedirect(route('admin.users.show', $admin))
            ->assertSessionHasErrors('user');
        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_fmo_head_can_create_staff_profile_manage_status_and_sync_skills(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $staffUser = $this->userWithRole('FMO Staff');
        $electrical = Skill::query()->where('name', 'Electrical')->firstOrFail();
        $plumbing = Skill::query()->where('name', 'Plumbing')->firstOrFail();

        $this->actingAs($head)->post(route('admin.staff.store'), [
            'user_id' => $staffUser->id, 'employee_code' => 'FMO-1001', 'position' => 'Technician',
            'designation' => 'Facilities', 'employment_status' => 'active', 'availability_status' => 'available',
            'skill_ids' => [$electrical->id],
        ])->assertRedirect();

        $profile = StaffProfile::query()->where('employee_code', 'FMO-1001')->firstOrFail();
        $this->actingAs($head)->put(route('admin.staff.update', $profile), [
            'user_id' => $staffUser->id, 'employee_code' => 'FMO-1001', 'position' => 'Senior Technician',
            'designation' => 'Facilities', 'employment_status' => 'active', 'availability_status' => 'busy',
        ])->assertRedirect(route('admin.staff.show', $profile));
        $this->assertSame('busy', $profile->refresh()->availability_status);

        $this->actingAs($head)->put(route('admin.staff.skills.update', $profile), ['skill_ids' => [$electrical->id, $plumbing->id]])
            ->assertRedirect(route('admin.staff.show', $profile));
        $this->assertEqualsCanonicalizing([$electrical->id, $plumbing->id], $profile->skills()->pluck('skills.id')->all());
    }

    public function test_fmo_head_can_create_and_deactivate_skill(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');

        $this->actingAs($head)->post(route('admin.skills.store'), ['name' => 'Generator Repair', 'description' => 'Generator servicing', 'is_active' => true])->assertRedirect();
        $skill = Skill::query()->where('name', 'Generator Repair')->firstOrFail();
        $this->actingAs($head)->put(route('admin.skills.update', $skill), ['name' => 'Generator Repair', 'description' => 'Updated', 'is_active' => false])
            ->assertRedirect(route('admin.skills.show', $skill));
        $this->assertFalse($skill->refresh()->is_active);
    }

    public function test_super_admin_can_create_role_and_assign_permissions_while_super_admin_role_is_protected(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Facilities Coordinator', 'permissions' => ['view_dashboard', 'view_work_orders'],
        ])->assertRedirect();
        $role = Role::findByName('Facilities Coordinator');
        $this->assertTrue($role->hasPermissionTo('view_work_orders'));

        $superAdmin = Role::findByName('Super Admin');
        $this->actingAs($admin)->from(route('admin.roles.show', $superAdmin))->put(route('admin.roles.update', $superAdmin), [
            'name' => 'Super Admin', 'permissions' => ['view_dashboard'],
        ])->assertRedirect(route('admin.roles.show', $superAdmin))->assertSessionHasErrors('name');
        $this->assertTrue($superAdmin->fresh()->hasPermissionTo('manage_permissions'));
    }

    public function test_admin_exports_respect_authorization(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');
        $head = $this->userWithRole('FMO Head');

        $this->actingAs($admin)->get(route('admin.users.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($head)->get(route('admin.staff.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($head)->get(route('admin.users.export'))->assertForbidden();
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
