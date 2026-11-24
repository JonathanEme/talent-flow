<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'linkedin_url' => $this->linkedin_url,
            'portfolio_url' => $this->portfolio_url,
            'cv_path' => $this->cv_path,
            'location' => $this->location,
            'salary_expectation' => $this->salary_expectation,
            'summary' => $this->summary,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'applications' => $this->whenLoaded('applications', fn () => ApplicationResource::collection($this->applications)),
            'skills' => $this->whenLoaded('skills', fn () => SkillResource::collection($this->skills)),
        ];
    }
}
