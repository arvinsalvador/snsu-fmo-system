<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\InventoryItem;
use App\Models\MaintenanceSchedule;
use App\Models\StaffProfile;
use App\Models\WorkOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public const REPORTS = ['work-orders', 'assets', 'maintenance-schedules', 'maintenance-records', 'inventory', 'staff'];

    public function dashboard(array $filters): array
    {
        $workOrders = $this->workOrderQuery($filters);
        $assets = $this->assetQuery($filters);
        $schedules = $this->scheduleQuery($filters);
        $maintenance = $this->maintenanceQuery($filters);
        $inventory = $this->inventoryQuery($filters);

        $metrics = [
            'total_assets' => (clone $assets)->count(),
            'active_assets' => (clone $assets)->where('status', 'active')->count(),
            'assets_under_maintenance' => (clone $assets)->where('status', 'under_maintenance')->count(),
            'defective_assets' => (clone $assets)->where('status', 'defective')->count(),
            'lost_assets' => (clone $assets)->where('status', 'lost')->count(),
            'disposed_assets' => (clone $assets)->whereIn('status', ['disposed', 'retired'])->count(),
            'total_work_orders' => (clone $workOrders)->count(),
            'open_work_orders' => (clone $workOrders)->whereHas('status', fn (Builder $query) => $query->whereNotIn('name', ['Completed', 'Evaluated', 'Closed', 'Cancelled']))->count(),
            'in_progress_work_orders' => (clone $workOrders)->whereHas('status', fn (Builder $query) => $query->where('name', 'In Progress'))->count(),
            'completed_work_orders' => (clone $workOrders)->whereHas('status', fn (Builder $query) => $query->whereIn('name', ['Completed', 'Evaluated', 'Closed']))->count(),
            'overdue_work_orders' => $this->overdueWorkOrders($workOrders)->count(),
            'preventive_maintenance_due' => (clone $schedules)->where('is_active', true)->whereBetween('next_due_date', [$this->from($filters)->toDateString(), $this->to($filters)->toDateString()])->count(),
            'preventive_maintenance_overdue' => (clone $schedules)->where('is_active', true)->whereDate('next_due_date', '<', now()->toDateString())->count(),
            'pending_maintenance_reviews' => (clone $maintenance)->where('review_status', 'pending_review')->count(),
            'correction_requests' => (clone $maintenance)->where('review_status', 'correction_requested')->count(),
            'approved_maintenance_records' => (clone $maintenance)->where('review_status', 'approved')->count(),
            'low_stock_items' => (clone $inventory)->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'out_of_stock_items' => (clone $inventory)->where('current_stock', '<=', 0)->count(),
            'maintenance_cost' => (float) (clone $maintenance)->where('review_status', 'approved')->sum(DB::raw('COALESCE(total_cost, labor_cost, 0)')),
        ];

        return [
            'metrics' => $metrics,
            'charts' => [
                'work_orders_by_status' => $this->groupWorkOrders($filters, 'work_order_statuses.name', 'work_order_statuses', 'status_id'),
                'assets_by_status' => $this->groupAssets($filters, 'status'),
                'assets_by_category' => $this->groupAssetsByCategory($filters),
                'maintenance_reviews' => $this->groupMaintenance($filters, 'review_status'),
                'work_order_trend' => $this->workOrderTrend($filters),
                'maintenance_cost_trend' => $this->maintenanceCostTrend($filters),
                'inventory_health' => $this->inventoryHealth($filters),
            ],
            'definitions' => $this->definitions(),
            'limitations' => $this->limitations(),
        ];
    }

    public function report(string $report, array $filters): array
    {
        abort_unless(in_array($report, self::REPORTS, true), 404);

        return [
            'title' => str($report)->replace('-', ' ')->title()->toString().' Report',
            'summary' => $this->summary($report, $filters),
            'charts' => $this->charts($report, $filters),
            'records' => $this->paginate($report, $filters),
            'columns' => $this->columns($report),
            'definitions' => $this->definitions(),
        ];
    }

    public function exportRows(string $report, array $filters): iterable
    {
        foreach ($this->query($report, $filters)->lazy(200) as $record) {
            yield $this->row($report, $record);
        }
    }

    public function columns(string $report): array
    {
        return match ($report) {
            'work-orders' => ['Number', 'Title', 'Status', 'Priority', 'Building', 'Department', 'Requested At', 'Target Date', 'Completed At'],
            'assets' => ['Asset Tag', 'Name', 'Category', 'Status', 'Building', 'Floor', 'Room', 'Acquisition Year', 'Maintenance Count', 'Maintenance Cost'],
            'maintenance-schedules' => ['Schedule', 'Asset', 'Frequency', 'Status', 'Next Due', 'Last Completed', 'Completion Count'],
            'maintenance-records' => ['Asset', 'Type', 'Technician', 'Maintenance Date', 'Review Status', 'Reviewer', 'Cost', 'Corrections'],
            'inventory' => ['Item Code', 'Item', 'Category', 'Unit', 'Current Stock', 'Minimum Stock', 'Status', 'Movement Count'],
            'staff' => ['Employee Code', 'Staff', 'Availability', 'Assignments', 'Pending Assignments', 'Completed Work Orders', 'Maintenance Tasks', 'Approved Maintenance', 'Correction Requests'],
            default => [],
        };
    }

    public function definitions(): array
    {
        return [
            'response_time' => 'Elapsed hours from requested_at to the first progress update. Records without a first progress update are excluded.',
            'resolution_time' => 'Elapsed hours from requested_at to completed_at. Incomplete records are excluded.',
            'overdue_work_order' => 'Target completion date is before today and the work order has no completed_at timestamp.',
            'maintenance_compliance' => 'Current-state approximation: linked completions on or before next due date divided by active schedules due in the selected period.',
            'maintenance_cost' => 'Sum of total_cost, falling back to labor_cost, for approved maintenance records in the selected period.',
            'inventory_balance' => 'Current stock stored on the inventory item and reconciled by immutable stock movements.',
            'approved_completion' => 'A maintenance record whose review_status is approved.',
        ];
    }

    public function limitations(): array
    {
        return [
            'The project currently represents one campus and has no user-to-location scope mapping; authorized report users receive campus-wide data constrained by selected location filters.',
            'Inventory valuation and highest-cost materials are unavailable because inventory items do not store unit cost.',
            'Historical preventive-maintenance due dates are not snapshotted, so compliance is a current-state approximation.',
            'Work-order reopened counts are unavailable because work-order status transitions are not stored as a dedicated status history.',
            'Evidence completeness is metadata-only; there is no required-evidence flag on maintenance records.',
        ];
    }

    private function summary(string $report, array $filters): array
    {
        return match ($report) {
            'work-orders' => $this->workOrderSummary($filters),
            'assets' => $this->assetSummary($filters),
            'maintenance-schedules' => $this->scheduleSummary($filters),
            'maintenance-records' => $this->maintenanceSummary($filters),
            'inventory' => $this->inventorySummary($filters),
            'staff' => $this->staffSummary($filters),
        };
    }

    private function charts(string $report, array $filters): array
    {
        return match ($report) {
            'work-orders' => ['by_status' => $this->groupWorkOrders($filters, 'work_order_statuses.name', 'work_order_statuses', 'status_id'), 'by_priority' => $this->groupWorkOrders($filters, 'priorities.name', 'priorities', 'priority_id'), 'trend' => $this->workOrderTrend($filters)],
            'assets' => ['by_status' => $this->groupAssets($filters, 'status'), 'by_category' => $this->groupAssetsByCategory($filters)],
            'maintenance-schedules' => ['by_frequency' => $this->groupSchedules($filters, 'frequency')],
            'maintenance-records' => ['by_review_status' => $this->groupMaintenance($filters, 'review_status'), 'cost_trend' => $this->maintenanceCostTrend($filters)],
            'inventory' => ['stock_status' => $this->inventoryHealth($filters)],
            'staff' => ['assignments' => $this->staffAssignments($filters)],
        };
    }

    private function paginate(string $report, array $filters): LengthAwarePaginator
    {
        $paginator = $this->query($report, $filters)->paginate($this->perPage($filters))->withQueryString();

        return $paginator->through(fn ($record): array => $this->row($report, $record));
    }

    private function query(string $report, array $filters): Builder
    {
        return match ($report) {
            'work-orders' => $this->workOrderQuery($filters)->with(['status', 'priority', 'building', 'department'])->orderBy($this->safeSort($filters, ['requested_at', 'target_completion_date', 'completed_at', 'work_order_number'], 'requested_at'), $this->direction($filters)),
            'assets' => $this->assetQuery($filters)->with(['category', 'building', 'floor', 'room'])->withCount('maintenanceRecords')->withSum('maintenanceRecords as maintenance_cost', 'total_cost')->orderBy($this->safeSort($filters, ['asset_tag', 'name', 'status', 'purchase_date'], 'asset_tag'), $this->direction($filters)),
            'maintenance-schedules' => $this->scheduleQuery($filters)->with('asset')->withCount('maintenanceRecords')->orderBy($this->safeSort($filters, ['next_due_date', 'last_completed_date', 'frequency', 'title'], 'next_due_date'), $this->direction($filters)),
            'maintenance-records' => $this->maintenanceQuery($filters)->with(['asset', 'maintenanceType', 'staffProfile.user', 'reviewer'])->withCount(['reviewActions as correction_count' => fn (Builder $query) => $query->where('action', 'corrected')])->orderBy($this->safeSort($filters, ['maintenance_date', 'review_status', 'total_cost'], 'maintenance_date'), $this->direction($filters)),
            'inventory' => $this->inventoryQuery($filters)->with('category')->withCount('stockMovements')->orderBy($this->safeSort($filters, ['item_code', 'name', 'current_stock', 'minimum_stock'], 'item_code'), $this->direction($filters)),
            'staff' => $this->staffQuery($filters)->with('user')->withCount([
                'assignments',
                'activeAssignments',
                'assignments as completed_work_orders_count' => fn (Builder $query) => $query->whereHas('workOrder', fn (Builder $query) => $query->whereNotNull('completed_at')),
                'maintenanceRecords',
                'maintenanceRecords as approved_maintenance_count' => fn (Builder $query) => $query->where('review_status', 'approved'),
                'maintenanceRecords as correction_requests_count' => fn (Builder $query) => $query->where('review_status', 'correction_requested'),
            ])->orderBy($this->safeSort($filters, ['employee_code', 'availability_status'], 'employee_code'), $this->direction($filters)),
        };
    }

    private function row(string $report, $record): array
    {
        return match ($report) {
            'work-orders' => ['id' => $record->id, 'url' => route('work-orders.show', $record), 'number' => $record->work_order_number, 'title' => $record->title, 'status' => $record->status?->name, 'priority' => $record->priority?->name, 'building' => $record->building?->name, 'department' => $record->department?->name, 'requested_at' => $record->requested_at?->toIso8601String(), 'target_date' => $record->target_completion_date?->toDateString(), 'completed_at' => $record->completed_at?->toIso8601String()],
            'assets' => ['id' => $record->id, 'url' => route('admin.assets.show', $record), 'asset_tag' => $record->asset_tag, 'name' => $record->name, 'category' => $record->category?->name, 'status' => $record->status, 'building' => $record->building?->name, 'floor' => $record->floor?->floor_name, 'room' => $record->room?->room_code, 'acquisition_year' => $record->purchase_date?->year, 'maintenance_count' => $record->maintenance_records_count, 'maintenance_cost' => (float) ($record->maintenance_cost ?? 0)],
            'maintenance-schedules' => ['id' => $record->id, 'url' => route('maintenance-schedules.show', $record), 'title' => $record->title, 'asset' => $record->asset?->asset_tag, 'frequency' => $record->frequency, 'status' => $record->due_status, 'next_due' => $record->next_due_date?->toDateString(), 'last_completed' => $record->last_completed_date?->toDateString(), 'completion_count' => $record->maintenance_records_count],
            'maintenance-records' => ['id' => $record->id, 'url' => route('admin.maintenance-reviews.show', $record), 'asset' => $record->asset?->asset_tag, 'type' => $record->maintenanceType?->name, 'technician' => $record->staffProfile?->user?->name ?? $record->performed_by, 'maintenance_date' => $record->maintenance_date?->toDateString(), 'review_status' => $record->review_status, 'reviewer' => $record->reviewer?->name, 'cost' => (float) ($record->total_cost ?? $record->labor_cost ?? 0), 'corrections' => $record->correction_count],
            'inventory' => ['id' => $record->id, 'url' => route('admin.inventory.show', $record), 'item_code' => $record->item_code, 'name' => $record->name, 'category' => $record->category?->name, 'unit' => $record->unit, 'current_stock' => (float) $record->current_stock, 'minimum_stock' => (float) $record->minimum_stock, 'status' => (float) $record->current_stock <= 0 ? 'out_of_stock' : ((float) $record->current_stock <= (float) $record->minimum_stock ? 'low_stock' : 'healthy'), 'movement_count' => $record->stock_movements_count],
            'staff' => ['id' => $record->id, 'url' => route('admin.staff.show', $record), 'employee_code' => $record->employee_code, 'name' => $record->user?->name, 'availability' => $record->availability_status, 'assignments' => $record->assignments_count, 'pending_assignments' => $record->active_assignments_count, 'completed_work_orders' => $record->completed_work_orders_count, 'maintenance_tasks' => $record->maintenance_records_count, 'approved_maintenance' => $record->approved_maintenance_count, 'correction_requests' => $record->correction_requests_count],
        };
    }

    private function workOrderSummary(array $filters): array
    {
        $query = $this->workOrderQuery($filters);
        $completed = (clone $query)->whereNotNull('completed_at');
        $resolved = (clone $completed)->whereNotNull('requested_at')->get(['requested_at', 'completed_at']);
        $response = (clone $query)->whereNotNull('requested_at')->withMin('updates', 'created_at')->get(['id', 'requested_at']);

        return [
            'total' => (clone $query)->count(),
            'overdue' => $this->overdueWorkOrders($query)->count(),
            'requiring_follow_up' => (clone $query)->whereHas('followups')->whereNull('completed_at')->count(),
            'reopened' => null,
            'average_resolution_hours' => $this->averageHours($resolved->map(fn (WorkOrder $order) => [$order->requested_at, $order->completed_at])),
            'average_response_hours' => $this->averageHours($response->filter(fn (WorkOrder $order) => $order->updates_min_created_at)->map(fn (WorkOrder $order) => [$order->requested_at, Carbon::parse($order->updates_min_created_at)])),
        ];
    }

    private function assetSummary(array $filters): array
    {
        $query = $this->assetQuery($filters);

        return [
            'total' => (clone $query)->count(),
            'without_photos' => (clone $query)->doesntHave('photos')->count(),
            'without_schedules' => (clone $query)->doesntHave('maintenanceSchedules')->count(),
            'overdue_maintenance' => (clone $query)->whereHas('maintenanceSchedules', fn (Builder $query) => $query->overdue())->count(),
            'repeated_maintenance' => (clone $query)->has('maintenanceRecords', '>=', 2)->count(),
            'maintenance_cost' => (float) $this->maintenanceQuery($filters)->where('review_status', 'approved')->sum(DB::raw('COALESCE(total_cost, labor_cost, 0)')),
        ];
    }

    private function scheduleSummary(array $filters): array
    {
        $query = $this->scheduleQuery($filters);
        $due = (clone $query)->where('is_active', true)->whereBetween('next_due_date', [$this->from($filters)->toDateString(), $this->to($filters)->toDateString()]);
        $dueCount = (clone $due)->count();
        $completedOnTime = (clone $due)->whereHas('maintenanceRecords', fn (Builder $query) => $query->whereColumn('completion_date', '<=', 'maintenance_schedules.next_due_date'))->count();

        return ['total' => (clone $query)->count(), 'upcoming' => (clone $query)->upcoming()->count(), 'overdue' => (clone $query)->overdue()->count(), 'completed' => (clone $query)->whereHas('maintenanceRecords', fn (Builder $query) => $query->whereBetween('completion_date', [$this->from($filters), $this->to($filters)]))->count(), 'compliance_rate' => $dueCount ? round(($completedOnTime / $dueCount) * 100, 2) : null, 'due_count' => $dueCount];
    }

    private function maintenanceSummary(array $filters): array
    {
        $query = $this->maintenanceQuery($filters);
        $reviewed = (clone $query)->whereNotNull('reviewed_at')->whereNotNull('created_at')->get(['created_at', 'reviewed_at']);

        return ['total' => (clone $query)->count(), 'pending_review' => (clone $query)->where('review_status', 'pending_review')->count(), 'correction_requested' => (clone $query)->where('review_status', 'correction_requested')->count(), 'approved' => (clone $query)->where('review_status', 'approved')->count(), 'rejected' => (clone $query)->where('review_status', 'rejected')->count(), 'total_cost' => (float) (clone $query)->where('review_status', 'approved')->sum(DB::raw('COALESCE(total_cost, labor_cost, 0)')), 'average_review_hours' => $this->averageHours($reviewed->map(fn (AssetMaintenanceRecord $record) => [$record->created_at, $record->reviewed_at]))];
    }

    private function inventorySummary(array $filters): array
    {
        $query = $this->inventoryQuery($filters);

        return ['items' => (clone $query)->count(), 'units' => (float) (clone $query)->sum('current_stock'), 'low_stock' => (clone $query)->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock')->count(), 'out_of_stock' => (clone $query)->where('current_stock', '<=', 0)->count(), 'estimated_value' => null];
    }

    private function staffSummary(array $filters): array
    {
        $query = $this->staffQuery($filters);

        return ['staff' => (clone $query)->count(), 'available' => (clone $query)->where('availability_status', 'available')->count(), 'pending_assignments' => DB::table('work_order_assignments')->whereNull('unassigned_at')->count(), 'performance_ranking' => null];
    }

    private function workOrderQuery(array $filters): Builder
    {
        return WorkOrder::query()->whereBetween('requested_at', [$this->from($filters), $this->to($filters)])
            ->when($filters['building_id'] ?? null, fn (Builder $query, $id) => $query->where('building_id', $id))->when($filters['floor_id'] ?? null, fn (Builder $query, $id) => $query->where('floor_id', $id))->when($filters['room_id'] ?? null, fn (Builder $query, $id) => $query->where('room_id', $id))->when($filters['work_order_status_id'] ?? null, fn (Builder $query, $id) => $query->where('status_id', $id))->when($filters['priority_id'] ?? null, fn (Builder $query, $id) => $query->where('priority_id', $id))->when($filters['staff_profile_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('assignments', fn (Builder $query) => $query->where('assigned_staff_id', $id)))->when($filters['search'] ?? null, fn (Builder $query, $search) => $query->where(fn (Builder $query) => $query->where('work_order_number', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%")));
    }

    private function assetQuery(array $filters): Builder
    {
        return Asset::query()->whereBetween('assets.created_at', [$this->from($filters), $this->to($filters)])
            ->when($filters['building_id'] ?? null, fn (Builder $query, $id) => $query->where('building_id', $id))->when($filters['floor_id'] ?? null, fn (Builder $query, $id) => $query->where('floor_id', $id))->when($filters['room_id'] ?? null, fn (Builder $query, $id) => $query->where('room_id', $id))->when($filters['asset_category_id'] ?? null, fn (Builder $query, $id) => $query->where('asset_category_id', $id))->when($filters['asset_status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))->when($filters['search'] ?? null, fn (Builder $query, $search) => $query->where(fn (Builder $query) => $query->where('asset_tag', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
    }

    private function scheduleQuery(array $filters): Builder
    {
        return MaintenanceSchedule::query()->whereBetween('created_at', [$this->from($filters), $this->to($filters)])->when($filters['building_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('asset', fn (Builder $query) => $query->where('building_id', $id)))->when($filters['floor_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('asset', fn (Builder $query) => $query->where('floor_id', $id)))->when($filters['room_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('asset', fn (Builder $query) => $query->where('room_id', $id)))->when($filters['asset_category_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('asset', fn (Builder $query) => $query->where('asset_category_id', $id)))->when($filters['maintenance_schedule_status'] ?? null, function (Builder $query, string $status): void {
            match ($status) {
                'upcoming' => $query->upcoming(), 'overdue' => $query->overdue(), 'inactive' => $query->where('is_active', false), 'scheduled' => $query->active()->whereDate('next_due_date', '>', now()->addDays(30)), default => null
            };
        })->when($filters['search'] ?? null, fn (Builder $query, $search) => $query->where('title', 'like', "%{$search}%"));
    }

    private function maintenanceQuery(array $filters): Builder
    {
        return AssetMaintenanceRecord::query()->whereBetween('maintenance_date', [$this->from($filters), $this->to($filters)])->when($filters['building_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('asset', fn (Builder $query) => $query->where('building_id', $id)))->when($filters['floor_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('asset', fn (Builder $query) => $query->where('floor_id', $id)))->when($filters['room_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('asset', fn (Builder $query) => $query->where('room_id', $id)))->when($filters['asset_category_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('asset', fn (Builder $query) => $query->where('asset_category_id', $id)))->when($filters['maintenance_review_status'] ?? null, fn (Builder $query, $status) => $query->where('review_status', $status))->when($filters['maintenance_type_id'] ?? null, fn (Builder $query, $id) => $query->where('maintenance_type_id', $id))->when($filters['staff_profile_id'] ?? null, fn (Builder $query, $id) => $query->where('staff_profile_id', $id));
    }

    private function inventoryQuery(array $filters): Builder
    {
        return InventoryItem::query()->when($filters['inventory_category_id'] ?? null, fn (Builder $query, $id) => $query->where('category_id', $id))->when($filters['search'] ?? null, fn (Builder $query, $search) => $query->where(fn (Builder $query) => $query->where('item_code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
    }

    private function staffQuery(array $filters): Builder
    {
        return StaffProfile::query()->where('employment_status', 'active')->when($filters['staff_profile_id'] ?? null, fn (Builder $query, $id) => $query->whereKey($id))->when($filters['search'] ?? null, fn (Builder $query, $search) => $query->where(fn (Builder $query) => $query->where('employee_code', 'like', "%{$search}%")->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))));
    }

    private function overdueWorkOrders(Builder $query): Builder
    {
        return (clone $query)->whereNull('completed_at')->whereDate('target_completion_date', '<', now()->toDateString());
    }

    private function groupAssets(array $filters, string $column): Collection
    {
        return $this->assetQuery($filters)->selectRaw("{$column} as label, COUNT(*) as value")->groupBy($column)->orderBy('label')->get();
    }

    private function groupAssetsByCategory(array $filters): Collection
    {
        return $this->assetQuery($filters)->leftJoin('asset_categories', 'asset_categories.id', '=', 'assets.asset_category_id')->selectRaw("COALESCE(asset_categories.name, 'Uncategorized') as label, COUNT(*) as value")->groupBy('asset_categories.name')->orderBy('label')->get();
    }

    private function groupMaintenance(array $filters, string $column): Collection
    {
        return $this->maintenanceQuery($filters)->selectRaw("{$column} as label, COUNT(*) as value")->groupBy($column)->orderBy('label')->get();
    }

    private function groupSchedules(array $filters, string $column): Collection
    {
        return $this->scheduleQuery($filters)->selectRaw("{$column} as label, COUNT(*) as value")->groupBy($column)->orderBy('label')->get();
    }

    private function groupWorkOrders(array $filters, string $column, string $table, string $foreign): Collection
    {
        return $this->workOrderQuery($filters)->join($table, "{$table}.id", '=', "work_orders.{$foreign}")->selectRaw("{$column} as label, COUNT(*) as value")->groupBy($column)->orderBy('label')->get();
    }

    private function inventoryHealth(array $filters): Collection
    {
        $query = $this->inventoryQuery($filters);

        return collect([['label' => 'Healthy', 'value' => (clone $query)->whereColumn('current_stock', '>', 'minimum_stock')->count()], ['label' => 'Low Stock', 'value' => (clone $query)->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock')->count()], ['label' => 'Out of Stock', 'value' => (clone $query)->where('current_stock', '<=', 0)->count()]]);
    }

    private function staffAssignments(array $filters): Collection
    {
        return $this->staffQuery($filters)->with('user')->withCount('assignments')->orderByDesc('assignments_count')->limit(10)->get()->map(fn (StaffProfile $staff) => ['label' => $staff->user?->name ?? $staff->employee_code, 'value' => $staff->assignments_count]);
    }

    private function workOrderTrend(array $filters): Collection
    {
        $month = $this->monthExpression('requested_at');

        return $this->workOrderQuery($filters)->selectRaw("{$month} as label, COUNT(*) as created_count, SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed_count")->groupBy('label')->orderBy('label')->get();
    }

    private function maintenanceCostTrend(array $filters): Collection
    {
        $month = $this->monthExpression('maintenance_date');

        return $this->maintenanceQuery($filters)->where('review_status', 'approved')->selectRaw("{$month} as label, SUM(COALESCE(total_cost, labor_cost, 0)) as value")->groupBy('label')->orderBy('label')->get();
    }

    private function monthExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%m', {$column})" : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function averageHours(Collection $pairs): ?float
    {
        if ($pairs->isEmpty()) {
            return null;
        }

        return round($pairs->avg(fn (array $pair) => $pair[0]->diffInMinutes($pair[1]) / 60), 2);
    }

    private function from(array $filters): Carbon
    {
        return Carbon::parse($filters['date_from'])->startOfDay();
    }

    private function to(array $filters): Carbon
    {
        return Carbon::parse($filters['date_to'])->endOfDay();
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 10), 100);
    }

    private function direction(array $filters): string
    {
        return ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
    }

    private function safeSort(array $filters, array $allowed, string $default): string
    {
        return in_array($filters['sort'] ?? '', $allowed, true) ? $filters['sort'] : $default;
    }
}
