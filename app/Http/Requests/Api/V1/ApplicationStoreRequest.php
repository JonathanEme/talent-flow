<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ApplicationStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-applications') ?? false;
    }

    public function rules(): array
    {
        return [
            'position_id' => ['required', 'exists:positions,id'],
            'candidate_id' => ['required', 'exists:candidates,id'],
            'stage' => ['nullable', 'string', Rule::in(ApplicationStage::initialValues())],
            'notes' => ['nullable', 'string', 'max:5000'],
            'applied_at' => ['nullable', 'date'],
        ];
    }
}
