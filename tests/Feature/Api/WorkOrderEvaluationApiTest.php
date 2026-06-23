<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkOrderEvaluationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_original_requestor_can_evaluate_completed_work_order_once(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $head = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder($requestor, 'Completed');

        Sanctum::actingAs($requestor);
        $this->getJson("/api/v1/work-orders/{$workOrder->id}/evaluation")
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/evaluation", [
            'rating' => 5,
            'comments' => 'The repair was completed professionally.',
        ])->assertCreated()
            ->assertJsonPath('data.work_order_uuid', $workOrder->uuid)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.evaluator.uuid', $requestor->uuid);

        $this->assertDatabaseHas('work_order_evaluations', [
            'work_order_id' => $workOrder->id,
            'evaluator_id' => $requestor->id,
            'rating' => 5,
        ]);
        $this->assertContains('evaluation_submitted', $head->notifications()->get()->pluck('data')->pluck('event')->all());

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/evaluation", ['rating' => 4])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('work_order');
        $this->assertDatabaseCount('work_order_evaluations', 1);
    }

    public function test_non_requestors_cannot_evaluate_even_with_super_admin_role(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $outsider = $this->userWithRole('Student');
        $superAdmin = $this->userWithRole('Super Admin');
        $workOrder = $this->workOrder($requestor, 'Completed');

        Sanctum::actingAs($outsider);
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/evaluation", ['rating' => 4])->assertForbidden();

        Sanctum::actingAs($superAdmin);
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/evaluation", ['rating' => 4])->assertForbidden();

        $this->assertDatabaseCount('work_order_evaluations', 0);
    }

    public function test_incomplete_work_orders_and_invalid_ratings_cannot_be_evaluated(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $submitted = $this->workOrder($requestor, 'Submitted');
        $completed = $this->workOrder($requestor, 'Completed');
        Sanctum::actingAs($requestor);

        $this->postJson("/api/v1/work-orders/{$submitted->id}/evaluation", ['rating' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('work_order');
        $this->postJson("/api/v1/work-orders/{$completed->id}/evaluation", ['rating' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rating');
    }

    public function test_evaluation_visibility_is_scoped_and_history_has_no_mutation_routes(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $head = $this->userWithRole('FMO Head');
        $outsider = $this->userWithRole('Student');
        $workOrder = $this->workOrder($requestor, 'Completed');

        Sanctum::actingAs($requestor);
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/evaluation", ['rating' => 4])->assertCreated();

        Sanctum::actingAs($head);
        $this->getJson("/api/v1/work-orders/{$workOrder->id}/evaluation")
            ->assertOk()
            ->assertJsonPath('data.rating', 4);

        Sanctum::actingAs($outsider);
        $this->getJson("/api/v1/work-orders/{$workOrder->id}/evaluation")->assertForbidden();

        $routes = app('router')->getRoutes();
        $this->assertFalse($routes->hasNamedRoute('api.v1.work-orders.evaluation.update'));
        $this->assertFalse($routes->hasNamedRoute('api.v1.work-orders.evaluation.destroy'));
    }

    public function test_web_form_submits_and_evaluation_appears_in_detail_and_timeline(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $workOrder = $this->workOrder($requestor, 'Completed');

        $this->actingAs($requestor)
            ->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Evaluate completed work')
            ->assertSee('Submit evaluation');

        $this->post(route('work-orders.evaluation.store', $workOrder), [
            'rating' => 3,
            'comments' => 'Good result with a longer wait than expected.',
        ])->assertRedirect(route('work-orders.show', $workOrder));

        $this->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Service evaluation')
            ->assertSee('3/5')
            ->assertSee('Service evaluated')
            ->assertSee('3 of 5 stars')
            ->assertSee('Good result with a longer wait than expected.');
    }

    private function seedFoundation(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function workOrder(User $requestor, string $status): WorkOrder
    {
        return WorkOrder::query()->create([
            'work_order_number' => 'WO-'.fake()->unique()->numerify('########'),
            'requestor_id' => $requestor->id,
            'department_id' => $this->tableId('departments', 'code', 'FMO'),
            'building_id' => $this->tableId('buildings', 'code', 'MAIN'),
            'floor_id' => $this->tableId('floors', 'floor_name', 'Ground Floor'),
            'room_id' => $this->tableId('rooms', 'room_code', 'MAIN-GF-001'),
            'category_id' => $this->tableId('work_order_categories', 'name', 'Electrical'),
            'priority_id' => $this->tableId('priorities', 'name', 'Normal'),
            'status_id' => $this->tableId('work_order_statuses', 'name', $status),
            'approval_status' => $status === 'Completed' ? 'approved' : 'pending',
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requested_at' => now()->subDay(),
            'completed_at' => $status === 'Completed' ? now() : null,
        ]);
    }

    private function tableId(string $table, string $column, string $value): int
    {
        return (int) app('db')->table($table)->where($column, $value)->value('id');
    }
}
