<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',

            'description' => 'nullable|string',

            'link' => 'nullable|url',

            'technologies' => 'nullable|array',
            'technologies.*' => 'string|max:100',
        ];
    }
}
