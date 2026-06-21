<?php

namespace App\Services;

use App\Models\StaffProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class StaffProfileService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($this->perPage($filters))->withQueryString();
    }

    public function records(array $filters = []): Collection
    {
        return $this->query($filters)->get();
    }

    public function create(array $data): StaffProfile
    {
        $skills = $data['skill_ids'] ?? [];
        unset($data['skill_ids']);
        $profile = StaffProfile::query()->create($data);
        $profile->skills()->sync($skills);

        return $profile->load('user.roles', 'skills');
    }

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

    public function syncSkills(StaffProfile $profile, array $skillIds): StaffProfile
    {
        $profile->skills()->sync($skillIds);

        return $profile->load('user.roles', 'skills');
    }

    private function query(array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? '', ['employee_code', 'position', 'employment_status', 'availability_status', 'created_at'], true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return StaffProfile::query()->with('user.roles', 'skills')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('employee_code', 'like', "%{$search}%")->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['availability'] ?? null, fn (Builder $query, string $status) => $query->where('availability_status', $status))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('employment_status', $status))
            ->when($filters['skill'] ?? null, fn (Builder $query, string $skill) => $query->whereHas('skills', fn (Builder $query) => $query->where('name', $skill)))
            ->orderBy($sort, $direction);
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 10), 100);
    }
}
