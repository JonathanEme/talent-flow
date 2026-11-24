<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\OfferStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OfferUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(OfferStatus::transitionalValues())],
            'decline_reason' => ['nullable', 'required_if:status,declined', 'string', 'max:2000'],
        ];
    }
}
