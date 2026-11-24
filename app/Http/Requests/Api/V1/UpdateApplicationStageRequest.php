<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ApplicationStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'string', Rule::in(ApplicationStage::values())],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
