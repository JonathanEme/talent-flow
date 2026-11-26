<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DomainValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterviewStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-interviews') ?? false;
    }

    public function rules(): array
    {
        return [
            'application_id' => ['required', 'exists:applications,id'],
            'interviewer_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', 'string', Rule::in(DomainValues::INTERVIEW_TYPES)],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'location_or_link' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
