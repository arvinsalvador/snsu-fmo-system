<?php

use App\Http\Controllers\Api\V1\AuthController;
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
use App\Http\Controllers\Api\V1\SkillController;
use App\Http\Controllers\Api\V1\StaffProfileController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkOrderAssignmentController;
use App\Http\Controllers\Api\V1\WorkOrderController;
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

        Route::apiResource('work-orders', WorkOrderController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);
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
    });
});
