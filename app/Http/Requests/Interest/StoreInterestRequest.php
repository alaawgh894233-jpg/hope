<?php

namespace App\Http\Requests\Interest;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'category' => 'nullable|in:technology,business,science,sports,art,education,other',
            'level' => 'nullable|integer|min:1|max:5',
            'description' => 'nullable|string|max:500',
        ];
    }
}
