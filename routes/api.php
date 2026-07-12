<?php

use App\Http\Controllers\Api\MaintenanceScheduleController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AssetMaintenanceRecordController;
use App\Http\Controllers\Api\V1\AssetMaintenanceReviewController;
use App\Http\Controllers\Api\V1\AssignmentIntelligenceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InventoryIntelligenceController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\KpiController;
use App\Http\Controllers\Api\V1\MasterData\AssetCategoryController;
use App\Http\Controllers\Api\V1\MasterData\BuildingController;
use App\Http\Controllers\Api\V1\MasterData\DepartmentController;
use App\Http\Controllers\Api\V1\MasterData\FloorController;
use App\Http\Controllers\Api\V1\MasterData\InventoryCategoryController;
use App\Http\Controllers\Api\V1\MasterData\MaintenanceTypeController;
use App\Http\Controllers\Api\V1\MasterData\PriorityController;
use App\Http\Controllers\Api\V1\MasterData\RoomController;
use App\Http\Controllers\Api\V1\MasterData\WorkOrderCategoryController;
use App\Http\Controllers\Api\V1\MasterData\WorkOrderStatusController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReportGenerationController;
use App\Http\Controllers\Api\V1\SkillController;
use App\Http\Controllers\Api\V1\StaffProfileController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkOrderAssignmentController;
use App\Http\Controllers\Api\V1\WorkOrderController;
use App\Http\Controllers\Api\V1\WorkOrderEvaluationController;
use App\Http\Controllers\Api\V1\WorkOrderFollowupController;
use App\Http\Controllers\Api\V1\WorkOrderMaterialController;
use App\Http\Controllers\Api\V1\WorkOrderUpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('kpi-definitions', [KpiController::class, 'definitions'])->name('kpi-definitions.index');
        Route::get('kpi-definitions/{kpiDefinition}', [KpiController::class, 'definition'])->name('kpi-definitions.show');
        Route::get('kpi-targets', [KpiController::class, 'targets'])->name('kpi-targets.index');
        Route::post('kpi-targets', [KpiController::class, 'storeTarget'])->name('kpi-targets.store');
        Route::get('kpi-targets/{kpiTarget}', [KpiController::class, 'target'])->name('kpi-targets.show');
        Route::patch('kpi-targets/{kpiTarget}', [KpiController::class, 'updateTarget'])->name('kpi-targets.update');
        Route::delete('kpi-targets/{kpiTarget}', [KpiController::class, 'destroyTarget'])->name('kpi-targets.destroy');
        Route::post('kpi-targets/{kpiTarget}/evaluate', [KpiController::class, 'evaluate'])->name('kpi-targets.evaluate');
        Route::get('kpi-targets/{kpiTarget}/evaluations', [KpiController::class, 'evaluations'])->name('kpi-targets.evaluations');
        Route::get('kpi-scorecard', [KpiController::class, 'scorecard'])->name('kpi-scorecard');
        Route::get('kpi-corrective-actions', [KpiController::class, 'actions'])->name('kpi-corrective-actions.index');
        Route::post('kpi-corrective-actions', [KpiController::class, 'storeAction'])->name('kpi-corrective-actions.store');
        Route::get('kpi-corrective-actions/{correctiveAction}', [KpiController::class, 'action'])->name('kpi-corrective-actions.show');
        Route::patch('kpi-corrective-actions/{correctiveAction}', [KpiController::class, 'updateAction'])->name('kpi-corrective-actions.update');
        Route::post('kpi-corrective-actions/{correctiveAction}/complete', [KpiController::class, 'transition'])->name('kpi-corrective-actions.complete');
        Route::post('kpi-corrective-actions/{correctiveAction}/cancel', [KpiController::class, 'transition'])->name('kpi-corrective-actions.cancel');
        Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update']);
        Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');

        Route::get('staff/workload-summary', [AssignmentIntelligenceController::class, 'workloadSummary'])
            ->name('staff.workload-summary');
        Route::get('staff/available', [AssignmentIntelligenceController::class, 'available'])
            ->name('staff.available');

        Route::apiResource('staff', StaffProfileController::class)
            ->parameters(['staff' => 'staffProfile'])
            ->only(['index', 'store', 'show', 'update']);
        Route::get('staff/{staffProfile}/skills', [StaffProfileController::class, 'skills'])->name('staff.skills.index');
        Route::put('staff/{staffProfile}/skills', [StaffProfileController::class, 'syncSkills'])->name('staff.skills.sync');

        Route::apiResource('skills', SkillController::class)->only(['index', 'store', 'show', 'update']);

        Route::apiResource('buildings', BuildingController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('floors', FloorController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('rooms', RoomController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('departments', DepartmentController::class)->only(['index', 'store', 'show', 'update']);

        Route::apiResource('work-order-categories', WorkOrderCategoryController::class)
            ->parameters(['work-order-categories' => 'workOrderCategory'])
            ->only(['index', 'store', 'show', 'update']);

        Route::apiResource('priorities', PriorityController::class)->only(['index', 'store', 'show', 'update']);

        Route::apiResource('work-order-statuses', WorkOrderStatusController::class)
            ->parameters(['work-order-statuses' => 'workOrderStatus'])
            ->only(['index', 'store', 'show', 'update']);

        Route::apiResource('asset-categories', AssetCategoryController::class)
            ->parameters(['asset-categories' => 'assetCategory'])
            ->only(['index', 'store', 'show', 'update']);

        Route::apiResource('maintenance-types', MaintenanceTypeController::class)
            ->parameters(['maintenance-types' => 'maintenanceType'])
            ->only(['index', 'store', 'show', 'update']);

        Route::apiResource('inventory-categories', InventoryCategoryController::class)
            ->parameters(['inventory-categories' => 'inventoryCategory'])
            ->only(['index', 'store', 'show', 'update']);

        Route::get('inventory-intelligence/dashboard', [InventoryIntelligenceController::class, 'dashboard'])->name('inventory-intelligence.dashboard');
        Route::get('inventory-intelligence/low-stock', [InventoryIntelligenceController::class, 'lowStock'])->name('inventory-intelligence.low-stock');
        Route::get('inventory-intelligence/out-of-stock', [InventoryIntelligenceController::class, 'outOfStock'])->name('inventory-intelligence.out-of-stock');
        Route::get('inventory-intelligence/fast-moving', [InventoryIntelligenceController::class, 'fastMoving'])->name('inventory-intelligence.fast-moving');
        Route::get('inventory-intelligence/slow-moving', [InventoryIntelligenceController::class, 'slowMoving'])->name('inventory-intelligence.slow-moving');
        Route::get('inventory-intelligence/monthly-consumption', [InventoryIntelligenceController::class, 'monthlyConsumption'])->name('inventory-intelligence.monthly-consumption');

        Route::apiResource('inventory-items', InventoryItemController::class)
            ->parameters(['inventory-items' => 'inventoryItem'])
            ->only(['index', 'store', 'show', 'update']);
        Route::post('inventory-items/{inventoryItem}/stock-in', [InventoryItemController::class, 'stockIn'])->name('inventory-items.stock-in');
        Route::post('inventory-items/{inventoryItem}/adjust', [InventoryItemController::class, 'adjust'])->name('inventory-items.adjust');
        Route::get('inventory-items/{inventoryItem}/movements', [InventoryItemController::class, 'movements'])->name('inventory-items.movements');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
            Route::get('work-orders', [ReportController::class, 'workOrders'])->name('work-orders');
            Route::get('assets', [ReportController::class, 'assets'])->name('assets');
            Route::get('maintenance-schedules', [ReportController::class, 'maintenanceSchedules'])->name('maintenance-schedules');
            Route::get('maintenance-records', [ReportController::class, 'maintenanceRecords'])->name('maintenance-records');
            Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
            Route::get('staff', [ReportController::class, 'staff'])->name('staff');
        });
        Route::get('report-templates', [ReportGenerationController::class, 'templates'])->name('report-templates.index');
        Route::get('report-templates/{reportTemplate}', [ReportGenerationController::class, 'template'])->name('report-templates.show');
        Route::post('reports/generate', [ReportGenerationController::class, 'generate'])->name('reports.generate');
        Route::get('generated-reports', [ReportGenerationController::class, 'generated'])->name('generated-reports.index');
        Route::get('generated-reports/{generatedReport}', [ReportGenerationController::class, 'show'])->name('generated-reports.show');
        Route::get('generated-reports/{generatedReport}/download', [ReportGenerationController::class, 'download'])->name('generated-reports.download');
        Route::get('generated-reports/{generatedReport}/verify', [ReportGenerationController::class, 'verify'])->name('generated-reports.verify');
        Route::get('report-schedules', [ReportGenerationController::class, 'schedules'])->name('report-schedules.index');
        Route::post('report-schedules', [ReportGenerationController::class, 'storeSchedule'])->name('report-schedules.store');
        Route::get('report-schedules/{reportSchedule}', [ReportGenerationController::class, 'schedule'])->name('report-schedules.show');
        Route::patch('report-schedules/{reportSchedule}', [ReportGenerationController::class, 'updateSchedule'])->name('report-schedules.update');
        Route::delete('report-schedules/{reportSchedule}', [ReportGenerationController::class, 'destroySchedule'])->name('report-schedules.destroy');
        Route::post('report-schedules/{reportSchedule}/run', [ReportGenerationController::class, 'run'])->name('report-schedules.run');
        Route::post('report-schedules/{reportSchedule}/enable', [ReportGenerationController::class, 'toggle'])->name('report-schedules.enable');
        Route::post('report-schedules/{reportSchedule}/disable', [ReportGenerationController::class, 'toggle'])->name('report-schedules.disable');
        Route::get('report-schedules/{reportSchedule}/deliveries', [ReportGenerationController::class, 'deliveries'])->name('report-schedules.deliveries');

        Route::get('assets/lookup', [AssetController::class, 'lookup'])->name('assets.lookup');
        Route::get('assets', [AssetController::class, 'index'])->name('assets.index');
        Route::post('assets', [AssetController::class, 'store'])->name('assets.store');
        Route::post('assets/{asset}/photos', [AssetController::class, 'photos'])->name('assets.photos.store');
        Route::get('assets/{asset}/maintenance-history', [AssetMaintenanceRecordController::class, 'assetHistory'])->name('assets.maintenance-history');
        Route::get('assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
        Route::match(['put', 'patch'], 'assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
        Route::get('asset-maintenance-records/export', [AssetMaintenanceRecordController::class, 'export'])->name('asset-maintenance-records.export');
        Route::apiResource('asset-maintenance-records', AssetMaintenanceRecordController::class)->only(['index', 'store', 'show']);
        Route::get('maintenance-reviews', [AssetMaintenanceReviewController::class, 'index'])->name('maintenance-reviews.index');
        Route::get('maintenance-records/{record}/review', [AssetMaintenanceReviewController::class, 'show'])->name('maintenance-records.review.show');
        Route::post('maintenance-records/{record}/submit-review', [AssetMaintenanceReviewController::class, 'resubmit'])->name('maintenance-records.review.submit');
        Route::post('maintenance-records/{record}/approve', [AssetMaintenanceReviewController::class, 'approve'])->name('maintenance-records.review.approve');
        Route::post('maintenance-records/{record}/request-correction', [AssetMaintenanceReviewController::class, 'requestCorrection'])->name('maintenance-records.review.request-correction');
        Route::patch('maintenance-records/{record}/correction', [AssetMaintenanceReviewController::class, 'correction'])->name('maintenance-records.review.correction');
        Route::post('maintenance-records/{record}/resubmit', [AssetMaintenanceReviewController::class, 'resubmit'])->name('maintenance-records.review.resubmit');
        Route::post('maintenance-records/{record}/reject', [AssetMaintenanceReviewController::class, 'reject'])->name('maintenance-records.review.reject');
        Route::post('maintenance-records/{record}/reopen', [AssetMaintenanceReviewController::class, 'reopen'])->name('maintenance-records.review.reopen');
        Route::get('maintenance-schedules/export', [MaintenanceScheduleController::class, 'export'])->name('maintenance-schedules.export');
        Route::get('maintenance-schedules/upcoming', [MaintenanceScheduleController::class, 'upcoming'])->name('maintenance-schedules.upcoming');
        Route::get('maintenance-schedules/overdue', [MaintenanceScheduleController::class, 'overdue'])->name('maintenance-schedules.overdue');
        Route::post('maintenance-schedules/{maintenanceSchedule}/complete', [MaintenanceScheduleController::class, 'complete'])->name('maintenance-schedules.complete');
        Route::apiResource('maintenance-schedules', MaintenanceScheduleController::class)
            ->parameters(['maintenance-schedules' => 'maintenanceSchedule']);

        Route::apiResource('work-orders', WorkOrderController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::get('work-orders/{workOrder}/materials', [WorkOrderMaterialController::class, 'index'])->name('work-orders.materials.index');
        Route::post('work-orders/{workOrder}/materials', [WorkOrderMaterialController::class, 'store'])->name('work-orders.materials.store');
        Route::patch('work-orders/{workOrder}/materials/{material}', [WorkOrderMaterialController::class, 'update'])->name('work-orders.materials.update');
        Route::delete('work-orders/{workOrder}/materials/{material}', [WorkOrderMaterialController::class, 'destroy'])->name('work-orders.materials.destroy');

        Route::get('work-orders/{workOrder}/followups', [WorkOrderFollowupController::class, 'index'])->name('work-orders.followups.index');
        Route::post('work-orders/{workOrder}/followups', [WorkOrderFollowupController::class, 'store'])->name('work-orders.followups.store');

        Route::get('work-orders/{workOrder}/evaluation', [WorkOrderEvaluationController::class, 'show'])->name('work-orders.evaluation.show');
        Route::post('work-orders/{workOrder}/evaluation', [WorkOrderEvaluationController::class, 'store'])->name('work-orders.evaluation.store');

        Route::post('work-orders/{workOrder}/approve', [WorkOrderController::class, 'approve'])
            ->name('work-orders.approve');
        Route::post('work-orders/{workOrder}/reject', [WorkOrderController::class, 'reject'])
            ->name('work-orders.reject');
        Route::get('work-orders/{workOrder}/approvals', [WorkOrderController::class, 'approvals'])
            ->name('work-orders.approvals.index');

        Route::post('work-orders/{workOrder}/assign', [WorkOrderAssignmentController::class, 'assign'])->name('work-orders.assign');
        Route::post('work-orders/{workOrder}/assign-team', [WorkOrderAssignmentController::class, 'assignTeam'])
            ->name('work-orders.assign-team');
        Route::post('work-orders/{workOrder}/reassign', [WorkOrderAssignmentController::class, 'reassign'])
            ->name('work-orders.reassign');
        Route::get('work-orders/{workOrder}/assignments', [WorkOrderAssignmentController::class, 'index'])
            ->name('work-orders.assignments.index');
        Route::get('work-orders/{workOrder}/assignment-recommendations', [AssignmentIntelligenceController::class, 'recommendations'])
            ->name('work-orders.assignment-recommendations');

        Route::get('work-orders/{workOrder}/updates', [WorkOrderUpdateController::class, 'index'])
            ->name('work-orders.updates.index');
        Route::post('work-orders/{workOrder}/updates', [WorkOrderUpdateController::class, 'store'])
            ->name('work-orders.updates.store');
        Route::post('work-orders/{workOrder}/updates/{update}/photos', [WorkOrderUpdateController::class, 'photos'])
            ->name('work-orders.updates.photos.store');
    });
});
