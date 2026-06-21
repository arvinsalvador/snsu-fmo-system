<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControlService
{
    public function paginateRoles(array $filters = []): LengthAwarePaginator
    {
        return $this->roleQuery($filters)
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function roles(array $filters = []): Collection
    {
        return $this->roleQuery($filters)->get();
    }

    public function paginatePermissions(array $filters = []): LengthAwarePaginator
    {
        return $this->permissionQuery($filters)
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function permissions(array $filters = []): Collection
    {
        return $this->permissionQuery($filters)->get();
    }

    public function createRole(array $data): Role
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        $role = Role::query()->create([...$data, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        return $role->load('permissions')->loadCount('users');
    }

    public function updateRole(Role $role, array $data): Role
    {
        if ($role->name === 'Super Admin') {
            throw ValidationException::withMessages(['name' => 'The Super Admin role is protected and cannot be modified.']);
        }

        $permissions = $data['permissions'] ?? null;
        unset($data['permissions']);
        $role->update($data);

        if ($permissions !== null) {
            $role->syncPermissions($permissions);
        }

        return $role->load('permissions')->loadCount('users');
    }

    private function roleQuery(array $filters)
    {
        $allowedSorts = ['name', 'created_at'];
        $sort = in_array($filters['sort'] ?? '', $allowedSorts, true) ? $filters['sort'] : 'name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy($sort, $direction);
    }

    private function permissionQuery(array $filters)
    {
        $allowedSorts = ['name', 'created_at'];
        $sort = in_array($filters['sort'] ?? '', $allowedSorts, true) ? $filters['sort'] : 'name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return Permission::query()
            ->where('guard_name', 'web')
            ->withCount('roles')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy($sort, $direction);
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 10), 100);
    }
}
