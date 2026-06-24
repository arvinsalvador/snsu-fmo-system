<?php

use App\Http\Controllers\Api\MaintenanceScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('assets', [MaintenanceScheduleController::class, 'assets']);
Route::get('maintenance-schedules/export', [MaintenanceScheduleController::class, 'export']);
Route::get('maintenance-schedules/upcoming', [MaintenanceScheduleController::class, 'upcoming']);
Route::get('maintenance-schedules/overdue', [MaintenanceScheduleController::class, 'overdue']);
Route::post('maintenance-schedules/{maintenanceSchedule}/complete', [MaintenanceScheduleController::class, 'complete']);
Route::apiResource('maintenance-schedules', MaintenanceScheduleController::class)->parameters(['maintenance-schedules' => 'maintenanceSchedule']);
