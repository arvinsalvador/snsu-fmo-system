<?php

namespace Tests\Feature\Api;

use App\Models\Skill;
use App\Models\StaffProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SkillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserStaffSkillApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_user(): void
    {
        $admin = $this->superAdmin();

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/users', [
            'employee_no' => 'EMP-1001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'username' => 'juan.delacruz',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => ['FMO Staff'],
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'juan@example.com')
            ->assertJsonPath('data.user.name', 'Juan Dela Cruz')
            ->assertJsonPath('data.user.roles.0', 'FMO Staff');

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'employee_no' => 'EMP-1001',
        ]);
    }

    public function test_user_without_manage_permission_cannot_create_user(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('FMO Staff');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/users', [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();
    }

    public function test_super_admin_can_create_staff_profile_with_skills(): void
    {
        $admin = $this->superAdmin();
        $this->seed(SkillSeeder::class);

        $staffUser = User::factory()->create([
            'employee_no' => 'EMP-2001',
            'first_name' => 'Pedro',
            'last_name' => 'Santos',
        ]);

        $skillIds = Skill::query()->whereIn('name', ['Carpentry', 'Electrical'])->pluck('id')->all();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/staff', [
            'user_id' => $staffUser->id,
            'employee_code' => 'FMO-2001',
            'position' => 'Maintenance Staff',
            'designation' => 'Field Operations',
            'employment_status' => 'active',
            'availability_status' => 'available',
            'skill_ids' => $skillIds,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.staff_profile.employee_code', 'FMO-2001')
            ->assertJsonCount(2, 'data.staff_profile.skills');

        $this->assertDatabaseHas('staff_profiles', [
            'user_id' => $staffUser->id,
            'employee_code' => 'FMO-2001',
        ]);
    }

    public function test_super_admin_can_sync_staff_skills(): void
    {
        $admin = $this->superAdmin();
        $profile = StaffProfile::factory()->create();
        $skills = Skill::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/staff/{$profile->id}/skills", [
            'skill_ids' => $skills->take(2)->pluck('id')->all(),
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.staff_profile.skills');
    }

    public function test_fmo_skills_are_seeded(): void
    {
        $this->seed(SkillSeeder::class);

        foreach (['Carpentry', 'Plumbing', 'Electrical', 'General Maintenance'] as $skill) {
            $this->assertDatabaseHas('skills', [
                'name' => $skill,
                'is_active' => true,
            ]);
        }
    }

    private function superAdmin(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create([
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('Super Admin');

        return $admin;
    }
}
