<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($this->perPage($filters))->withQueryString();
    }

    public function records(array $filters = []): Collection
    {
        return $this->query($filters)->get();
    }

    public function create(array $data): User
    {
        $roles = $data['roles'] ?? [];
        unset($data['roles']);
        $user = User::query()->create($data);
        $user->syncRoles($roles);

        return $user->load('roles', 'staffProfile');
    }

    public function update(User $user, array $data, ?User $actor = null): User
    {
        $roles = $data['roles'] ?? null;
        if ($roles !== null && $actor?->is($user) && $user->hasRole('Super Admin') && ! in_array('Super Admin', $roles, true)) {
            throw ValidationException::withMessages(['roles' => 'You cannot remove your own Super Admin role.']);
        }
        unset($data['roles']);
        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        }
        if (array_intersect(['first_name', 'middle_name', 'last_name', 'suffix'], array_keys($data)) !== []) {
            $user->fill($data);
            $data['name'] = User::resolveDisplayName($user);
        }
        $user->update($data);
        if ($roles !== null) {
            $user->syncRoles($roles);
        }

        return $user->load('roles', 'staffProfile');
    }

    public function setActive(User $user, bool $isActive, ?User $actor = null): User
    {
        if (! $isActive && $actor?->is($user)) {
            throw ValidationException::withMessages(['user' => 'You cannot deactivate your own account.']);
        }

        $user->forceFill(['is_active' => $isActive])->save();

        return $user->load('roles', 'staffProfile');
    }

    private function query(array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? '', ['name', 'email', 'employee_no', 'created_at', 'is_active'], true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return User::query()->with('roles', 'staffProfile')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_no', 'like', "%{$search}%")->orWhere('student_no', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->role($role))
            ->orderBy($sort, $direction);
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 10), 100);
    }
}
