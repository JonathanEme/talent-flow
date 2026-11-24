<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'application_id' => $this->resource['application_id'],
            'candidate' => $this->resource['candidate'],
            'position' => $this->resource['position'],
            'current_stage' => $this->resource['current_stage'],
            'timeline' => TimelineEventResource::collection($this->resource['timeline']),
        ];
    }
}
