<?php

namespace App\Services;

use App\Models\StaffProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StaffProfileService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return StaffProfile::query()
            ->with('user.roles', 'skills')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['availability'] ?? null, fn ($query, string $status) => $query->where('availability_status', $status))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('employment_status', $status))
            ->when($filters['skill'] ?? null, fn ($query, string $skill) => $query->whereHas('skills', fn ($query) => $query->where('name', $skill)))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StaffProfile
    {
        $skills = $data['skill_ids'] ?? [];
        unset($data['skill_ids']);

        $profile = StaffProfile::query()->create($data);
        $profile->skills()->sync($skills);

        return $profile->load('user.roles', 'skills');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(StaffProfile $profile, array $data): StaffProfile
    {
        $skills = $data['skill_ids'] ?? null;
        unset($data['skill_ids']);

        $profile->update($data);

        if ($skills !== null) {
            $profile->skills()->sync($skills);
        }

        return $profile->load('user.roles', 'skills');
    }

    /**
     * @param  array<int, int>  $skillIds
     */
    public function syncSkills(StaffProfile $profile, array $skillIds): StaffProfile
    {
        $profile->skills()->sync($skillIds);

        return $profile->load('user.roles', 'skills');
    }
}
