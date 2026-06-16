<?php

namespace App\Services;

use App\Models\Skill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SkillService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Skill::query()
            ->withCount('staffProfiles')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->when(array_key_exists('status', $filters), function ($query) use ($filters): void {
                if ($filters['status'] === 'active') {
                    $query->where('is_active', true);
                }

                if ($filters['status'] === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Skill
    {
        return Skill::query()->create($data)->loadCount('staffProfiles');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Skill $skill, array $data): Skill
    {
        $skill->update($data);

        return $skill->loadCount('staffProfiles');
    }
}
