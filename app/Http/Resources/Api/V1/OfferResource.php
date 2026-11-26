<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'created_by' => $this->created_by,
            'salary_offered' => $this->salary_offered,
            'currency' => $this->currency,
            'start_date' => $this->start_date,
            'expiration_date' => $this->expiration_date,
            'benefits_summary' => $this->benefits_summary,
            'terms_and_conditions' => $this->terms_and_conditions,
            'status' => $this->status,
            'decline_reason' => $this->decline_reason,
            'responded_at' => $this->responded_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'application' => $this->whenLoaded('application', fn () => new ApplicationResource($this->application)),
            'creator' => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),
        ];
    }
}
