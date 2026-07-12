<?php

namespace App\Http\Requests\Api\V1\Reports;

use App\Models\Asset;
use App\Models\Floor;
use App\Models\Room;
use App\Services\AssetMaintenanceReviewService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    public const PRESETS = ['today', 'this_week', 'this_month', 'this_quarter', 'this_year', 'previous_month', 'previous_quarter', 'previous_year', 'custom'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $preset = $this->input('period', 'this_month');
        if ($preset === 'custom') {
            return;
        }

        [$from, $to] = $this->periodDates($preset);
        $this->merge(['period' => $preset, 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString()]);
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(self::PRESETS)],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'campus' => ['nullable', Rule::in(['SNSU Del Carmen Campus'])],
            'building_id' => ['nullable', Rule::exists('buildings', 'id')->whereNull('deleted_at')],
            'floor_id' => ['nullable', Rule::exists('floors', 'id')->whereNull('deleted_at')],
            'room_id' => ['nullable', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
            'asset_category_id' => ['nullable', Rule::exists('asset_categories', 'id')->whereNull('deleted_at')],
            'asset_status' => ['nullable', Rule::in(Asset::STATUSES)],
            'work_order_status_id' => ['nullable', Rule::exists('work_order_statuses', 'id')->whereNull('deleted_at')],
            'priority_id' => ['nullable', Rule::exists('priorities', 'id')->whereNull('deleted_at')],
            'maintenance_schedule_status' => ['nullable', Rule::in(['upcoming', 'overdue', 'inactive', 'scheduled'])],
            'maintenance_review_status' => ['nullable', Rule::in(AssetMaintenanceReviewService::STATUSES)],
            'maintenance_type_id' => ['nullable', Rule::exists('maintenance_types', 'id')->whereNull('deleted_at')],
            'staff_profile_id' => ['nullable', Rule::exists('staff_profiles', 'id')->whereNull('deleted_at')],
            'inventory_category_id' => ['nullable', Rule::exists('inventory_categories', 'id')->whereNull('deleted_at')],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:50'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $buildingId = $this->integer('building_id') ?: null;
            $floorId = $this->integer('floor_id') ?: null;
            $roomId = $this->integer('room_id') ?: null;
            if ($floorId && $buildingId && ! Floor::query()->whereKey($floorId)->where('building_id', $buildingId)->exists()) {
                $validator->errors()->add('floor_id', 'The floor does not belong to the selected building.');
            }
            if ($roomId) {
                $room = Room::query()->with('floor')->find($roomId);
                if ($floorId && $room?->floor_id !== $floorId) {
                    $validator->errors()->add('room_id', 'The room does not belong to the selected floor.');
                }
                if ($buildingId && $room?->floor?->building_id !== $buildingId) {
                    $validator->errors()->add('room_id', 'The room does not belong to the selected building.');
                }
            }
        }];
    }

    private function periodDates(string $preset): array
    {
        $now = Carbon::now();

        return match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'previous_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'previous_quarter' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'previous_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
