<?php

namespace Tests\Feature\Api;

use App\Models\Skill;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SkillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssignmentIntelligenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendations_include_skill_match_preference_workload_and_score(): void
    {
        $this->seedFoundation();
        $viewer = $this->userWithRole('FMO Head');
        $electricalSkill = Skill::query()->where('name', 'Electrical')->firstOrFail();
        $preferredStaff = StaffProfile::factory()->create(['availability_status' => 'busy']);
        $otherStaff = StaffProfile::factory()->create(['availability_status' => 'available']);
        $preferredStaff->skills()->attach($electricalSkill);
        $otherStaff->skills()->attach($electricalSkill);
        $workOrder = $this->createWorkOrder(preferredStaffId: $preferredStaff->id);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/v1/work-orders/{$workOrder->id}/assignment-recommendations")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.staff_profile.id', $preferredStaff->id)
            ->assertJsonPath('data.0.user.name', $preferredStaff->user->name)
            ->assertJsonPath('data.0.availability_status', 'busy')
            ->assertJsonPath('data.0.matched_skills.0.name', 'Electrical')
            ->assertJsonPath('data.0.active_assigned_count', 0)
            ->assertJsonPath('data.0.pending_assigned_count', 0)
            ->assertJsonPath('data.0.in_progress_assigned_count', 0)
            ->assertJsonPath('data.0.is_preferred_staff', true)
            ->assertJsonPath('data.0.recommendation_score', 75);

        $this->assertDatabaseCount('work_order_assignments', 0);
    }

    public function test_workload_summary_counts_nonterminal_pending_and_in_progress_assignments(): void
    {
        $this->seedFoundation();
        $viewer = $this->userWithRole('Campus Director');
        $staff = StaffProfile::factory()->create();

        foreach (['Assigned', 'In Progress', 'Completed'] as $status) {
            $workOrder = $this->createWorkOrder(statusName: $status);
            WorkOrderAssignment::query()->create([
                'work_order_id' => $workOrder->id,
                'assigned_staff_id' => $staff->id,
                'assigned_by' => $viewer->id,
                'assignment_type' => 'individual',
                'assigned_at' => now(),
            ]);
        }

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/staff/workload-summary')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.staff_profile.id', $staff->id)
            ->assertJsonPath('data.0.active_assigned_count', 2)
            ->assertJsonPath('data.0.pending_assigned_count', 1)
            ->assertJsonPath('data.0.in_progress_assigned_count', 1);
    }

    public function test_available_staff_lookup_filters_availability_without_enforcing_assignment(): void
    {
        $this->seedFoundation();
        $viewer = $this->userWithRole('Director for Instruction');
        $available = StaffProfile::factory()->create(['availability_status' => 'available']);
        StaffProfile::factory()->create(['availability_status' => 'busy']);
        StaffProfile::factory()->create(['employment_status' => 'inactive', 'availability_status' => 'available']);
        StaffProfile::factory()->create([
            'user_id' => User::factory()->create(['is_active' => false])->id,
            'availability_status' => 'available',
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/staff/available')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.staff_profile.id', $available->id)
            ->assertJsonPath('data.0.availability_status', 'available');
    }

    public function test_staff_lookup_supports_skill_and_search_filters(): void
    {
        $this->seedFoundation();
        $viewer = $this->userWithRole('FMO Head');
        $electricalSkill = Skill::query()->where('name', 'Electrical')->firstOrFail();
        $matchingStaff = StaffProfile::factory()->create(['employee_code' => 'FMO-ELECTRIC']);
        $matchingStaff->skills()->attach($electricalSkill);
        StaffProfile::factory()->create(['employee_code' => 'FMO-OTHER']);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/v1/staff/workload-summary?skill_id={$electricalSkill->id}&search=ELECTRIC")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.staff_profile.id', $matchingStaff->id);
    }

    public function test_user_without_intelligence_permissions_cannot_access_endpoints(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');
        $workOrder = $this->createWorkOrder($faculty);

        Sanctum::actingAs($faculty);

        $this->getJson("/api/v1/work-orders/{$workOrder->id}/assignment-recommendations")
            ->assertForbidden();
        $this->getJson('/api/v1/staff/workload-summary')
            ->assertForbidden();
        $this->getJson('/api/v1/staff/available')
            ->assertForbidden();
    }

    private function seedFoundation(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(SkillSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createWorkOrder(
        ?User $requestor = null,
        ?int $preferredStaffId = null,
        string $statusName = 'Approved',
    ): WorkOrder {
        $requestor ??= $this->userWithRole('Faculty');

        return WorkOrder::query()->create([
            'work_order_number' => 'WO-'.fake()->unique()->numerify('########'),
            'requestor_id' => $requestor->id,
            'department_id' => $this->tableId('departments', 'code', 'FMO'),
            'building_id' => $this->tableId('buildings', 'code', 'MAIN'),
            'floor_id' => $this->tableId('floors', 'floor_name', 'Ground Floor'),
            'room_id' => $this->tableId('rooms', 'room_code', 'MAIN-GF-001'),
            'category_id' => $this->tableId('work_order_categories', 'name', 'Electrical'),
            'priority_id' => $this->tableId('priorities', 'name', 'Normal'),
            'status_id' => $this->tableId('work_order_statuses', 'name', $statusName),
            'approval_status' => 'approved',
            'preferred_staff_id' => $preferredStaffId,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requested_at' => now(),
        ]);
    }

    private function tableId(string $table, string $column, string $value): int
    {
        return (int) app('db')->table($table)->where($column, $value)->value('id');
    }
}
