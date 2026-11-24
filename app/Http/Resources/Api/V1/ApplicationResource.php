<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position_id' => $this->position_id,
            'candidate_id' => $this->candidate_id,
            'status' => $this->status,
            'stage' => $this->stage,
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->notes,
            'applied_at' => $this->applied_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'candidate' => $this->whenLoaded('candidate', fn () => new CandidateResource($this->candidate)),
            'position' => $this->whenLoaded('position', fn () => new PositionResource($this->position)),
            'stageLogs' => $this->whenLoaded('stageLogs', fn () => ApplicationStageLogResource::collection($this->stageLogs)),
            'interviews' => $this->whenLoaded('interviews', fn () => InterviewResource::collection($this->interviews)),
            'offers' => $this->whenLoaded('offers', fn () => OfferResource::collection($this->offers)),
        ];
    }
}
