<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kpis' => $this->resource['kpis'],
            'stage_distribution' => $this->resource['stage_distribution'],
            'status_distribution' => $this->resource['status_distribution'],
            'upcoming_interviews' => InterviewResource::collection($this->resource['upcoming_interviews']),
            'recent_positions' => PositionResource::collection($this->resource['recent_positions']),
        ];
    }
}
