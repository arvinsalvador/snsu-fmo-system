<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterData\StoreBuildingRequest;
use App\Http\Requests\Api\V1\MasterData\StoreDepartmentRequest;
use App\Http\Requests\Api\V1\MasterData\StoreFloorRequest;
use App\Http\Requests\Api\V1\MasterData\StoreNameRequest;
use App\Http\Requests\Api\V1\MasterData\StorePriorityRequest;
use App\Http\Requests\Api\V1\MasterData\StoreRoomRequest;
use App\Http\Requests\Api\V1\MasterData\StoreWorkOrderStatusRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateBuildingRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateDepartmentRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateFloorRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateNameRequest;
use App\Http\Requests\Api\V1\MasterData\UpdatePriorityRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateRoomRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateWorkOrderStatusRequest;
use App\Services\AdminWebService;
use App\Services\MasterDataService;
use App\Services\MasterDataWebService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataController extends Controller
{
    public function __construct(
        private readonly MasterDataService $masterData,
        private readonly MasterDataWebService $web,
        private readonly AdminWebService $adminWeb,
    ) {}

    public function index(Request $request): View
    {
        $module = $this->module();
        Gate::authorize('manageMasterData', $module['model']);
        $filters = $request->only(['search', 'status', 'sort', 'direction', 'per_page']);

        return view('admin.master-data.index', [
            'moduleKey' => $this->moduleKey(), 'module' => $module,
            'modules' => $this->web->modules(), 'filters' => $filters,
            'records' => $this->masterData->paginate($module['model'], $filters, $module['with']),
            'web' => $this->web,
        ]);
    }

    public function create(): View
    {
        $module = $this->module();
        Gate::authorize('manageMasterData', $module['model']);

        return $this->formView($module);
    }

    public function show(Request $request): View
    {
        $record = $this->record($request);
        Gate::authorize('view', $record);
        $record->load($this->module()['with']);

        return view('admin.master-data.show', $this->viewData(['record' => $record]));
    }

    public function edit(Request $request): View
    {
        $record = $this->record($request);
        Gate::authorize('update', $record);

        return $this->formView($this->module(), $record);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $record = $this->record($request);
        Gate::authorize('delete', $record);
        $this->masterData->delete($record);

        return redirect()->route("admin.master-data.{$this->moduleKey()}.index")->with('success', 'Record deleted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $module = $this->module();
        Gate::authorize('manageMasterData', $module['model']);
        $records = $this->masterData->records($module['model'], $request->only(['search', 'status', 'sort', 'direction']), $module['with']);

        return $this->adminWeb->csv(
            $this->moduleKey(),
            [...array_values($module['columns']), 'Active', 'Created At'],
            $records,
            fn (Model $record): array => [
                ...array_map(fn (string $key): string => $this->web->displayValue($record, $key), array_keys($module['columns'])),
                $record->is_active ? 'Yes' : 'No',
                $record->created_at?->toIso8601String(),
            ],
        );
    }

    public function storeBuilding(StoreBuildingRequest $request): RedirectResponse
    {
        return $this->storeRecord($request);
    }

    public function updateBuilding(UpdateBuildingRequest $request): RedirectResponse
    {
        return $this->updateRecord($request);
    }

    public function storeFloor(StoreFloorRequest $request): RedirectResponse
    {
        return $this->storeRecord($request);
    }

    public function updateFloor(UpdateFloorRequest $request): RedirectResponse
    {
        return $this->updateRecord($request);
    }

    public function storeRoom(StoreRoomRequest $request): RedirectResponse
    {
        return $this->storeRecord($request);
    }

    public function updateRoom(UpdateRoomRequest $request): RedirectResponse
    {
        return $this->updateRecord($request);
    }

    public function storeDepartment(StoreDepartmentRequest $request): RedirectResponse
    {
        return $this->storeRecord($request);
    }

    public function updateDepartment(UpdateDepartmentRequest $request): RedirectResponse
    {
        return $this->updateRecord($request);
    }

    public function storeCategory(StoreNameRequest $request): RedirectResponse
    {
        return $this->storeRecord($request);
    }

    public function updateCategory(UpdateNameRequest $request): RedirectResponse
    {
        return $this->updateRecord($request);
    }

    public function storePriority(StorePriorityRequest $request): RedirectResponse
    {
        return $this->storeRecord($request);
    }

    public function updatePriority(UpdatePriorityRequest $request): RedirectResponse
    {
        return $this->updateRecord($request);
    }

    public function storeStatus(StoreWorkOrderStatusRequest $request): RedirectResponse
    {
        return $this->storeRecord($request);
    }

    public function updateStatus(UpdateWorkOrderStatusRequest $request): RedirectResponse
    {
        return $this->updateRecord($request);
    }

    public function modelTable(): string
    {
        return (new ($this->module()['model']))->getTable();
    }

    public function routeParameter(): string
    {
        return $this->module()['parameter'];
    }

    private function storeRecord(FormRequest $request): RedirectResponse
    {
        $module = $this->module();
        Gate::authorize('manageMasterData', $module['model']);
        $record = $this->masterData->create($module['model'], $request->validated());

        return redirect()->route("admin.master-data.{$this->moduleKey()}.show", $record)->with('success', 'Record created successfully.');
    }

    private function updateRecord(FormRequest $request): RedirectResponse
    {
        $record = $this->record($request);
        Gate::authorize('update', $record);
        $this->masterData->update($record, $request->validated());

        return redirect()->route("admin.master-data.{$this->moduleKey()}.show", $record)->with('success', 'Record updated successfully.');
    }

    private function record(Request $request): Model
    {
        $record = $request->route($this->routeParameter());

        return $record instanceof Model ? $record : $this->module()['model']::query()->findOrFail($record);
    }

    private function formView(array $module, ?Model $record = null): View
    {
        return view('admin.master-data.form', $this->viewData(['record' => $record, 'options' => $this->web->options()]));
    }

    private function viewData(array $extra = []): array
    {
        return [...['moduleKey' => $this->moduleKey(), 'module' => $this->module(), 'modules' => $this->web->modules(), 'web' => $this->web], ...$extra];
    }

    private function module(): array
    {
        return $this->web->module($this->moduleKey());
    }

    private function moduleKey(): string
    {
        return (string) request()->route('module');
    }
}
