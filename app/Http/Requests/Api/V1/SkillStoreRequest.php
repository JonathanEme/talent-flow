<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DomainValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkillStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-skills') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:skills,name'],
            'category' => ['nullable', 'string', Rule::in(DomainValues::SKILL_CATEGORIES)],
        ];
    }
}
