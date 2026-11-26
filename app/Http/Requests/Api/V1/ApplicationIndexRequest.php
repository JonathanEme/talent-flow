<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'status' => ['nullable', 'string', Rule::in(ApplicationStatus::values())],
            'stage' => ['nullable', 'string', Rule::in(ApplicationStage::values())],
        ];
    }
}
