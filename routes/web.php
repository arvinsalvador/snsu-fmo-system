<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MasterDataController;
use App\Http\Controllers\Web\WorkOrderController;
use App\Http\Controllers\Web\WorkOrderWorkflowController;
use App\Models\Building;
use App\Models\Department;
use App\Models\Floor;
use App\Models\Priority;
use App\Models\Room;
use App\Models\WorkOrderCategory;
use App\Models\WorkOrderStatus;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
    Route::get('/my-requests', [WorkOrderController::class, 'myRequests'])->name('my-requests');
    Route::get('/work-orders/create', [WorkOrderController::class, 'create'])->name('work-orders.create');
    Route::post('/work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store');
    Route::get('/work-orders/approval-queue', [WorkOrderController::class, 'approvalQueue'])->name('work-orders.approval-queue');
    Route::get('/work-orders/assignment-queue', [WorkOrderController::class, 'assignmentQueue'])->name('work-orders.assignment-queue');
    Route::get('/assigned-tasks', [WorkOrderController::class, 'assignedTasks'])->name('work-orders.assigned-tasks');
    Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
    Route::get('/work-orders/{workOrder}/recommendations', [WorkOrderController::class, 'recommendations'])->name('work-orders.recommendations');
    Route::get('/work-orders/{workOrder}/progress/create', [WorkOrderController::class, 'createProgress'])->name('work-orders.progress.create');

    Route::post('/work-orders/{workOrder}/approve', [WorkOrderWorkflowController::class, 'approve'])->name('work-orders.approve');
    Route::post('/work-orders/{workOrder}/reject', [WorkOrderWorkflowController::class, 'reject'])->name('work-orders.reject');
    Route::post('/work-orders/{workOrder}/assign', [WorkOrderWorkflowController::class, 'assign'])->name('work-orders.assign');
    Route::post('/work-orders/{workOrder}/reassign', [WorkOrderWorkflowController::class, 'reassign'])->name('work-orders.reassign');
    Route::post('/work-orders/{workOrder}/progress', [WorkOrderWorkflowController::class, 'progress'])->name('work-orders.progress.store');

    $masterDataModules = [
        'buildings' => ['building', 'storeBuilding', 'updateBuilding', Building::class],
        'floors' => ['floor', 'storeFloor', 'updateFloor', Floor::class],
        'rooms' => ['room', 'storeRoom', 'updateRoom', Room::class],
        'departments' => ['department', 'storeDepartment', 'updateDepartment', Department::class],
        'work-order-categories' => ['workOrderCategory', 'storeCategory', 'updateCategory', WorkOrderCategory::class],
        'priorities' => ['priority', 'storePriority', 'updatePriority', Priority::class],
        'work-order-statuses' => ['workOrderStatus', 'storeStatus', 'updateStatus', WorkOrderStatus::class],
    ];

    Route::prefix('admin/master-data')->name('admin.master-data.')->group(function () use ($masterDataModules) {
        foreach ($masterDataModules as $module => [$parameter, $store, $update, $model]) {
            Route::model($parameter, $model);
            Route::get("/{$module}", [MasterDataController::class, 'index'])->defaults('module', $module)->name("{$module}.index");
            Route::get("/{$module}/create", [MasterDataController::class, 'create'])->defaults('module', $module)->name("{$module}.create");
            Route::get("/{$module}/export", [MasterDataController::class, 'export'])->defaults('module', $module)->name("{$module}.export");
            Route::post("/{$module}", [MasterDataController::class, $store])->defaults('module', $module)->name("{$module}.store");
            Route::get("/{$module}/{{$parameter}}", [MasterDataController::class, 'show'])->defaults('module', $module)->name("{$module}.show");
            Route::get("/{$module}/{{$parameter}}/edit", [MasterDataController::class, 'edit'])->defaults('module', $module)->name("{$module}.edit");
            Route::put("/{$module}/{{$parameter}}", [MasterDataController::class, $update])->defaults('module', $module)->name("{$module}.update");
            Route::delete("/{$module}/{{$parameter}}", [MasterDataController::class, 'destroy'])->defaults('module', $module)->name("{$module}.destroy");
        }
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
