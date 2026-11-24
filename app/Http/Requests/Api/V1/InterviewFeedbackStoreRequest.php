<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DomainValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterviewFeedbackStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:1', 'max:10'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'weaknesses' => ['nullable', 'string', 'max:5000'],
            'general_notes' => ['nullable', 'string', 'max:5000'],
            'recommendation' => ['required', 'string', Rule::in(DomainValues::RECOMMENDATIONS)],
        ];
    }
}
