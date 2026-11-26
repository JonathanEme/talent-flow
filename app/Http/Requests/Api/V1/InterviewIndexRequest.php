<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DomainValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterviewIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'status' => ['nullable', 'string', Rule::in(DomainValues::INTERVIEW_STATUSES)],
            'scheduled_date' => ['nullable', 'date'],
        ];
    }
}
