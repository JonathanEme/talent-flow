<?php

namespace App\Http\Requests\Api\V1;

use App\Support\DomainValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OfferStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_id' => ['required', 'exists:applications,id'],
            'salary_offered' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', Rule::in(DomainValues::CURRENCIES)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'benefits_summary' => ['nullable', 'string', 'max:5000'],
            'terms_and_conditions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
