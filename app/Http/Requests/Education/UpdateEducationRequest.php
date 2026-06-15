<?php

namespace App\Http\Requests\Education;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution' => 'sometimes|string|max:255',
            'degree' => 'sometimes|string|max:255',
            'field_of_study' => 'nullable|string|max:255',

            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date',

            'grade' => 'nullable|integer|min:0|max:100'
        ];
    }
}
