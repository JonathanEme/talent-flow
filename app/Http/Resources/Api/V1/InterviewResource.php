<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'interviewer_id' => $this->interviewer_id,
            'type' => $this->type,
            'scheduled_at' => $this->scheduled_at,
            'duration_minutes' => $this->duration_minutes,
            'location_or_link' => $this->location_or_link,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'application' => $this->whenLoaded('application', fn () => new ApplicationResource($this->application)),
            'interviewer' => $this->whenLoaded('interviewer', fn () => new UserResource($this->interviewer)),
            'feedbacks' => $this->whenLoaded('feedbacks', fn () => InterviewFeedbackResource::collection($this->feedbacks)),
        ];
    }
}
