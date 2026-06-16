<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserManagementService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->with('roles', 'staffProfile')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_no', 'like', "%{$search}%")
                        ->orWhere('student_no', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('status', $filters), function ($query) use ($filters): void {
                if ($filters['status'] === 'active') {
                    $query->where('is_active', true);
                }

                if ($filters['status'] === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->role($role))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $user = User::query()->create($data);
        $user->syncRoles($roles);

        return $user->load('roles', 'staffProfile');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $roles = $data['roles'] ?? null;
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

    public function setActive(User $user, bool $isActive): User
    {
        $user->forceFill(['is_active' => $isActive])->save();

        return $user->load('roles', 'staffProfile');
    }
}
