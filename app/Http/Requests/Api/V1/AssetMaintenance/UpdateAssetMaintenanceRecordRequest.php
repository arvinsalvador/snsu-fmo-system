<?php

namespace App\Http\Requests\Api\V1\AssetMaintenance;

class UpdateAssetMaintenanceRecordRequest extends StoreAssetMaintenanceRecordRequest
{
    public function authorize(): bool
    {
        $record = $this->route('assetMaintenanceRecord') ?? $this->route('asset_maintenance_record');

        return $record && ($this->user()?->can('update', $record) ?? false);
    }

    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules): array => array_values(array_filter(
                $rules,
                fn (mixed $rule): bool => $rule !== 'required',
            )))
            ->all();
    }
}
