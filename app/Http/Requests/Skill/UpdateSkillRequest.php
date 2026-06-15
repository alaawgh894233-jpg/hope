<?php

namespace App\Http\Requests\Skill;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',

            'type' => 'sometimes|in:technical,tool,language,soft_skill',

            'level' => 'sometimes|in:beginner,intermediate,advanced',

            'years_experience' => 'nullable|integer|min:0|max:50',
        ];
    }
}
