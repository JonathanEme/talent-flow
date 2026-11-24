<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'candidate_id' => $this->resource['candidate_id'],
            'candidate_name' => $this->resource['candidate_name'],
            'email' => $this->resource['email'] ?? null,
            'overall_score' => $this->resource['overall_score'],
            'meets_all_mandatory' => $this->resource['meets_all_mandatory'],
            'mandatory_matched' => $this->resource['mandatory_matched'],
            'mandatory_total' => $this->resource['mandatory_total'],
            'optional_matched' => $this->resource['optional_matched'],
            'optional_total' => $this->resource['optional_total'],
            'details' => $this->resource['details'],
            'factors' => $this->resource['factors'] ?? [],
            'already_applied' => (bool) ($this->resource['already_applied'] ?? false),
            'application_stage' => $this->resource['application_stage'] ?? null,
        ];
    }
}
