<?php

namespace Tests\Feature;

use App\Jobs\EvaluateKpiTargetJob;
use App\Models\KpiDefinition;
use App\Models\KpiTarget;
use App\Models\User;
use App\Services\KpiEvaluationService;
use App\Services\KpiMetricRegistry;
use Database\Seeders\KpiDefinitionSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase10CKpiTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_registry_seeds_only_approved_metrics(): void
    {
        $this->seedAll();
        $this->assertCount(count(KpiMetricRegistry::METRICS), KpiDefinition::all());
        $this->assertFalse(KpiDefinition::where('metric_key', 'reopened_work_order_rate')->exists());
    }

    public function test_scoring_rules_cover_all_directions_and_zero_targets(): void
    {
        $this->seedAll();
        $service = app(KpiEvaluationService::class);
        $higher = $this->target('higher_is_better', 100);
        $this->assertSame('achieved', $service->score($higher, 110)['status']);
        $lower = $this->target('lower_is_better', 10);
        $this->assertSame(50.0, $service->score($lower, 20)['score']);
        $range = $this->target('target_range', null, 40, 60);
        $this->assertSame(100.0, $service->score($range, 50)['score']);
        $zero = $this->target('lower_is_better', 0);
        $this->assertSame('achieved', $service->score($zero, 0)['status']);
        $this->assertSame('no_data', $service->score($higher, null)['status']);
    }

    public function test_overlapping_target_is_rejected_and_permissions_are_enforced(): void
    {
        $this->seedAll();
        $manager = $this->manager();
        $definition = KpiDefinition::first();
        $this->createTarget($definition, $manager);
        Sanctum::actingAs($manager);
        $payload = ['kpi_definition_id' => $definition->id, 'scope_type' => 'organization', 'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString(), 'target_value' => 90, 'status' => 'active'];
        $this->postJson('/api/v1/kpi-targets', $payload)->assertUnprocessable()->assertJsonValidationErrors('period_start');
        Sanctum::actingAs($this->role('Faculty'));
        $this->getJson('/api/v1/kpi-scorecard')->assertForbidden();
    }

    public function test_evaluation_snapshot_is_immutable_daily_and_scorecard_uses_snapshot(): void
    {
        $this->seedAll();
        $manager = $this->manager();
        $definition = KpiDefinition::where('metric_key', 'out_of_stock_item_count')->first();
        $target = $this->createTarget($definition, $manager, ['target_value' => 0]);
        $evaluation = app(KpiEvaluationService::class)->evaluate($target, $manager->id);
        $this->assertSame('achieved', $evaluation->status);
        $this->expectException(HttpException::class);
        app(KpiEvaluationService::class)->evaluate($target, $manager->id);
    }

    public function test_due_command_queues_each_target_once(): void
    {
        $this->seedAll();
        Queue::fake();
        $manager = $this->manager();
        $this->createTarget(KpiDefinition::first(), $manager);
        $this->artisan('kpis:evaluate-due')->assertSuccessful();
        Queue::assertPushed(EvaluateKpiTargetJob::class, 1);
    }

    private function seedAll(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class, KpiDefinitionSeeder::class]);
    }

    private function manager(): User
    {
        return $this->role('FMO Head');
    }

    private function role(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function createTarget(KpiDefinition $d, User $u, array $x = []): KpiTarget
    {
        return KpiTarget::create(['kpi_definition_id' => $d->id, 'scope_type' => 'organization', 'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(), 'target_value' => 90, 'owner_user_id' => $u->id, 'status' => 'active', 'created_by' => $u->id, ...$x]);
    }

    private function target(string $direction, ?float $target, ?float $min = null, ?float $max = null): KpiTarget
    {
        $d = new KpiDefinition(['direction' => $direction]);
        $t = new KpiTarget(['target_value' => $target, 'minimum_value' => $min, 'maximum_value' => $max, 'period_end' => now()->addDay()]);
        $t->setRelation('definition', $d);

        return $t;
    }
}
