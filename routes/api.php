<?php

use App\Http\Controllers\Api\MaintenanceScheduleController;
use App\Http\Controllers\Api\V1\AssignmentIntelligenceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InventoryIntelligenceController;
use App\Http\Controllers\Api\V1\InventoryItemController;
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

        Route::get('assets', [MaintenanceScheduleController::class, 'assets'])->name('assets.index');
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