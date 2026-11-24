<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewFeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'interview_id' => $this->interview_id,
            'user_id' => $this->user_id,
            'score' => $this->score,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'general_notes' => $this->general_notes,
            'recommendation' => $this->recommendation,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'evaluator' => $this->whenLoaded('evaluator', fn () => new UserResource($this->evaluator)),
        ];
    }
}
