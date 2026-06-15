<?php

namespace App\Http\Requests\Experience;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company' => 'sometimes|string|max:255',
            'position' => 'sometimes|string|max:255',

            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date',

            'is_current' => 'sometimes|boolean',

            'description' => 'nullable|string',

            'technologies_used' => 'nullable|array',
            'technologies_used.*' => 'string|max:100',
        ];
    }
}
