<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource['type'],
            'title' => $this->resource['title'],
            'details' => $this->resource['details'],
            'timestamp' => $this->resource['timestamp'],
        ];
    }
}
