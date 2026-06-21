<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentRecommendationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->resource['staff_profile'];

        return [
            'staff_profile' => [
                'id' => $profile->id,
                'user_id' => $profile->user_id,
                'employee_code' => $profile->employee_code,
                'position' => $profile->position,
                'designation' => $profile->designation,
                'employment_status' => $profile->employment_status,
                'availability_status' => $profile->availability_status,
            ],
            'user' => [
                'id' => $profile->user?->id,
                'uuid' => $profile->user?->uuid,
                'name' => $profile->user?->name,
            ],
            'availability_status' => $profile->availability_status,
            'matched_skills' => SkillResource::collection($this->resource['matched_skills']),
            'active_assigned_count' => (int) $profile->active_assigned_count,
            'pending_assigned_count' => (int) $profile->pending_assigned_count,
            'in_progress_assigned_count' => (int) $profile->in_progress_assigned_count,
            'is_preferred_staff' => $this->resource['is_preferred_staff'],
            'recommendation_score' => $this->resource['recommendation_score'],
        ];
    }
}
