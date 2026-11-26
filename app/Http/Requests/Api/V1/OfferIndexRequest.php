<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\OfferStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OfferIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(OfferStatus::values())],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
        ];
    }
}
