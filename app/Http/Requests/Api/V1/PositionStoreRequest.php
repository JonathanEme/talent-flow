<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DomainValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-positions') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'seniority' => ['required', 'string', 'max:50', Rule::in(DomainValues::SENIORITIES)],
            'modality' => ['required', 'string', Rule::in(DomainValues::MODALITIES)],
            'location' => ['nullable', 'string', 'max:150'],
            'employment_type' => ['required', 'string', Rule::in(DomainValues::EMPLOYMENT_TYPES)],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'vacancies' => ['nullable', 'integer', 'min:1'],
            'opened_at' => ['required', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'status' => ['nullable', 'string', Rule::in(DomainValues::POSITION_STATUSES)],
        ];
    }
}
