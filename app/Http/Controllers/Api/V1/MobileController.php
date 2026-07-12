<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileAssetResource;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenanceRecord;
use App\Models\Building;
use App\Models\Floor;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\KpiCorrectiveAction;
use App\Models\MaintenanceType;
use App\Models\Priority;
use App\Models\Room;
use App\Models\WorkOrder;
use App\Models\WorkOrderCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileController extends Controller
{
    public function dashboard(Request $r): JsonResponse
    {
        $u = $r->user();
        $staff = $u->staffProfile?->id;
        $assigned = $staff ? WorkOrder::whereHas('activeAssignments', fn ($q) => $q->where('assigned_staff_id', $staff))->whereNull('completed_at')->count() : 0;
        $data = ['assigned_work_orders' => $assigned, 'overdue_work_orders' => $staff ? WorkOrder::whereHas('activeAssignments', fn ($q) => $q->where('assigned_staff_id', $staff))->whereNull('completed_at')->whereDate('target_completion_date', '<', today())->count() : 0, 'unread_notifications' => $u->unreadNotifications()->count(), 'assigned_corrective_actions' => KpiCorrectiveAction::where('assigned_to', $u->id)->whereIn('status', ['open', 'in_progress'])->count()];
        if ($u->can('approve_work_orders')) {
            $data['pending_approvals'] = WorkOrder::where('approval_status', 'pending')->count();
        }if ($u->can('view_inventory')) {
            $data['low_stock_alerts'] = InventoryItem::whereColumn('current_stock', '<=', 'minimum_stock')->count();
        }if ($u->can('view_maintenance_reviews')) {
            $data['pending_maintenance_reviews'] = AssetMaintenanceRecord::where('review_status', 'pending_review')->count();
        }

        return response()->json(['success' => true, 'message' => 'Mobile dashboard retrieved successfully.', 'data' => $data, 'meta' => ['server_time' => now()->utc()->toIso8601String()]]);
    }

    public function asset(Request $r, string $identifier): JsonResponse
    {
        $asset = Asset::with(['category', 'building', 'floor', 'room', 'maintenanceSchedules'])->where(fn ($q) => $q->where('qr_token', $identifier)->orWhere('uuid', $identifier)->orWhere('asset_tag', $identifier)->orWhere('serial_number', $identifier))->firstOrFail();
        abort_unless($r->user()->can('view', $asset), 403);

        return response()->json(['success' => true, 'message' => 'Asset retrieved successfully.', 'data' => new MobileAssetResource($asset), 'meta' => null]);
    }

    public function references(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Reference data retrieved successfully.', 'data' => ['buildings' => Building::where('is_active', true)->get(['id', 'uuid', 'name']), 'floors' => Floor::where('is_active', true)->get(['id', 'uuid', 'building_id', 'floor_name']), 'rooms' => Room::where('is_active', true)->get(['id', 'uuid', 'floor_id', 'room_code', 'name']), 'priorities' => Priority::where('is_active', true)->get(['id', 'uuid', 'name', 'level']), 'work_order_categories' => WorkOrderCategory::where('is_active', true)->get(['id', 'uuid', 'name']), 'asset_categories' => AssetCategory::where('is_active', true)->get(['id', 'uuid', 'name']), 'maintenance_types' => MaintenanceType::where('is_active', true)->get(['id', 'uuid', 'name']), 'inventory_categories' => InventoryCategory::where('is_active', true)->get(['id', 'uuid', 'name'])], 'meta' => ['cached_for_seconds' => 300]]);
    }

    public function info(): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'System information retrieved successfully.', 'data' => ['api_version' => 'v1', 'application_version' => config('app.version', '1.0.0'), 'minimum_mobile_version' => config('mobile.minimum_supported_version'), 'server_time' => now()->utc()->toIso8601String(), 'maintenance_mode' => app()->isDownForMaintenance(), 'features' => config('mobile.features'), 'upload_limits' => config('mobile.uploads')], 'meta' => null]);
    }
}
