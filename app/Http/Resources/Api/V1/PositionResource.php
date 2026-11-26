<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'title' => $this->title,
            'description' => $this->description,
            'seniority' => $this->seniority,
            'modality' => $this->modality,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'vacancies' => $this->vacancies,
            'opened_at' => $this->opened_at,
            'closes_at' => $this->closes_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
            'skills' => $this->whenLoaded('skills', fn () => SkillResource::collection($this->skills)),
            'applications_count' => $this->when(isset($this->applications_count), $this->applications_count),
        ];
    }
}
