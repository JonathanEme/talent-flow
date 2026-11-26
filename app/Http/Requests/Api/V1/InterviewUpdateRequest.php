<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DomainValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterviewUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-interviews') ?? false;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
            'location_or_link' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(DomainValues::INTERVIEW_STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
