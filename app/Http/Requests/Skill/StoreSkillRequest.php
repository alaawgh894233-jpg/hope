<?php

namespace App\Http\Requests\Skill;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',

            'type' => 'required|in:technical,tool,language,soft_skill',

            'level' => 'required|in:beginner,intermediate,advanced',

            'years_experience' => 'nullable|integer|min:0|max:50',
        ];
    }
}
