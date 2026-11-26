<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DomainValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'status' => ['nullable', 'string', Rule::in(DomainValues::POSITION_STATUSES)],
        ];
    }
}
