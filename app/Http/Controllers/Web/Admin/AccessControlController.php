<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreRoleRequest;
use App\Http\Requests\Web\UpdateRoleRequest;
use App\Services\AccessControlService;
use App\Services\AdminWebService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccessControlController extends Controller
{
    public function __construct(private readonly AccessControlService $access, private readonly AdminWebService $web) {}

    public function roles(Request $request): View
    {
        Gate::authorize('manage_roles');
        $filters = $request->only(['search', 'sort', 'direction', 'per_page']);

        return view('admin.access.roles-index', ['roles' => $this->access->paginateRoles($filters), 'filters' => $filters]);
    }

    public function createRole(): View
    {
        Gate::authorize('manage_roles');

        return view('admin.access.role-form', ['role' => null, 'permissions' => $this->web->permissions()]);
    }

    public function storeRole(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->access->createRole($request->validated());

        return redirect()->route('admin.roles.show', $role)->with('success', 'Role created successfully.');
    }

    public function showRole(Role $role): View
    {
        Gate::authorize('manage_roles');

        return view('admin.access.role-show', ['role' => $role->load('permissions')->loadCount('users')]);
    }

    public function editRole(Role $role): View
    {
        Gate::authorize('manage_roles');

        return view('admin.access.role-form', ['role' => $role->load('permissions'), 'permissions' => $this->web->permissions()]);
    }

    public function updateRole(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->access->updateRole($role, $request->validated());

        return redirect()->route('admin.roles.show', $role)->with('success', 'Role updated successfully.');
    }

    public function exportRoles(Request $request): StreamedResponse
    {
        Gate::authorize('manage_roles');

        return $this->web->csv('roles', ['Role', 'Users', 'Permissions'], $this->access->roles($request->only(['search', 'sort', 'direction'])), fn (Role $role): array => [
            $role->name, $role->users_count, $role->permissions->pluck('name')->implode('; '),
        ]);
    }

    public function permissions(Request $request): View
    {
        Gate::authorize('manage_permissions');
        $filters = $request->only(['search', 'sort', 'direction', 'per_page']);

        return view('admin.access.permissions-index', ['permissions' => $this->access->paginatePermissions($filters), 'filters' => $filters]);
    }

    public function showPermission(Permission $permission): View
    {
        Gate::authorize('manage_permissions');

        return view('admin.access.permission-show', ['permission' => $permission->load('roles')->loadCount('roles')]);
    }

    public function exportPermissions(Request $request): StreamedResponse
    {
        Gate::authorize('manage_permissions');

        return $this->web->csv('permissions', ['Permission', 'Roles', 'Created At'], $this->access->permissions($request->only(['search', 'sort', 'direction'])), fn (Permission $permission): array => [
            $permission->name, $permission->roles_count, $permission->created_at?->toIso8601String(),
        ]);
    }
}
