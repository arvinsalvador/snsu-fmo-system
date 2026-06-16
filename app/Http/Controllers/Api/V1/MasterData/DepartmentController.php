<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Requests\Api\V1\MasterData\StoreDepartmentRequest;
use App\Http\Requests\Api\V1\MasterData\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends MasterDataController
{
    public function modelClass(): string
    {
        return Department::class;
    }

    public function routeParameter(): string
    {
        return 'department';
    }

    public function index(Request $request): JsonResponse
    {
        return $this->indexResponse($request, 'Departments retrieved successfully.');
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        return $this->storeResponse($request, 'Department created successfully.', 'department');
    }

    public function show(Department $department): JsonResponse
    {
        return $this->showResponse($department, 'Department retrieved successfully.', 'department');
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        return $this->updateResponse($request, $department, 'Department updated successfully.', 'department');
    }
}
