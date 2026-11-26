<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CandidateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'skill' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:150'],
        ];
    }
}
