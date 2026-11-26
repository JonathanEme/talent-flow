<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'pivot' => $this->when($this->pivot !== null, function (): array {
                $pivot = [];
                $attributes = $this->pivot->getAttributes();

                if (array_key_exists('is_mandatory', $attributes)) {
                    $pivot['is_mandatory'] = (bool) $this->pivot->is_mandatory;
                }

                if (array_key_exists('min_years_experience', $attributes)) {
                    $pivot['min_years_experience'] = (int) $this->pivot->min_years_experience;
                }

                if (array_key_exists('expected_level', $attributes)) {
                    $pivot['expected_level'] = $this->pivot->expected_level;
                }

                if (array_key_exists('years_experience', $attributes)) {
                    $pivot['years_experience'] = (int) $this->pivot->years_experience;
                }

                if (array_key_exists('level', $attributes)) {
                    $pivot['level'] = $this->pivot->level;
                }

                return $pivot;
            }),
        ];
    }
}
