<?php

namespace App\Services;

use App\Models\StaffProfile;
use App\Models\WorkOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class AssignmentIntelligenceService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function workloadSummary(array $filters = []): LengthAwarePaginator
    {
        return $this->staffQuery()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(
                $filters['skill_id'] ?? null,
                fn (Builder $query, int $skillId) => $query->whereHas('skills', fn (Builder $query) => $query->whereKey($skillId)),
            )
            ->when(
                $filters['availability_status'] ?? null,
                fn (Builder $query, string $status) => $query->where('availability_status', $status),
            )
            ->orderBy('active_assigned_count')
            ->orderBy('employee_code')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function availableStaff(array $filters = []): LengthAwarePaginator
    {
        return $this->workloadSummary([
            ...$filters,
            'availability_status' => 'available',
        ]);
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function recommendations(WorkOrder $workOrder): SupportCollection
    {
        $workOrder->loadMissing('category');
        $targetSkills = $this->targetSkillNames($workOrder);

        return $this->staffQuery()
            ->get()
            ->map(function (StaffProfile $profile) use ($targetSkills, $workOrder): array {
                $matchedSkills = $profile->skills
                    ->filter(fn ($skill): bool => $skill->is_active
                        && $targetSkills->contains($this->normalize($skill->name)))
                    ->values();
                $isPreferred = $workOrder->preferred_staff_id === $profile->id;

                return [
                    'staff_profile' => $profile,
                    'matched_skills' => $matchedSkills,
                    'is_preferred_staff' => $isPreferred,
                    'recommendation_score' => $this->score($profile, $matchedSkills, $isPreferred),
                ];
            })
            ->sortBy([
                ['recommendation_score', 'desc'],
                [fn (array $recommendation): int => $recommendation['staff_profile']->active_assigned_count, 'asc'],
                [fn (array $recommendation): string => $recommendation['staff_profile']->user?->name ?? '', 'asc'],
            ])
            ->values();
    }

    private function staffQuery(): Builder
    {
        return StaffProfile::query()
            ->where('employment_status', 'active')
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->with([
                'user',
                'skills' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            ])
            ->withCount([
                'activeAssignments as active_assigned_count' => fn (Builder $query) => $query
                    ->whereHas('workOrder.status', fn (Builder $query) => $query
                        ->whereNotIn('name', ['Completed', 'Evaluated', 'Closed', 'Cancelled'])),
                'activeAssignments as pending_assigned_count' => fn (Builder $query) => $query
                    ->whereHas('workOrder.status', fn (Builder $query) => $query->where('name', 'Assigned')),
                'activeAssignments as in_progress_assigned_count' => fn (Builder $query) => $query
                    ->whereHas('workOrder.status', fn (Builder $query) => $query->where('name', 'In Progress')),
            ]);
    }

    /**
     * @return SupportCollection<int, string>
     */
    private function targetSkillNames(WorkOrder $workOrder): SupportCollection
    {
        $category = $this->normalize($workOrder->category?->name ?? '');

        return collect($category === 'others' ? ['general maintenance'] : [$category]);
    }

    /**
     * @param  Collection<int, mixed>  $matchedSkills
     */
    private function score(StaffProfile $profile, Collection $matchedSkills, bool $isPreferred): int
    {
        $skillScore = $matchedSkills->isEmpty() ? 0 : 50 + (($matchedSkills->count() - 1) * 10);
        $preferredScore = $isPreferred ? 25 : 0;
        $availabilityScore = $profile->availability_status === 'available' ? 15 : 0;
        $workloadPenalty = min(((int) $profile->active_assigned_count) * 5, 30);

        return max(0, $skillScore + $preferredScore + $availabilityScore - $workloadPenalty);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->trim()->lower()->toString();
    }
}
