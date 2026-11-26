<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DomainValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncCandidateSkillsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['required', 'array'],
            'skills.*.skill_id' => ['required', 'exists:skills,id', 'distinct'],
            'skills.*.years_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'skills.*.level' => ['nullable', 'string', Rule::in(DomainValues::SKILL_LEVELS)],
        ];
    }
}
