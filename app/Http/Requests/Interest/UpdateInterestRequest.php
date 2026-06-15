<?php

namespace App\Http\Requests\Interest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
            'category' => 'sometimes|in:technology,business,science,sports,art,education,other',
            'level' => 'sometimes|integer|min:1|max:5',
            'description' => 'nullable|string|max:500',
        ];
    }
}
