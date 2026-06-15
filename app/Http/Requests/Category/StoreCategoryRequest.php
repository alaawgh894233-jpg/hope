<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:100|unique:categories,name',
            'name_ar' => 'nullable|string|max:100',
            'type'    => 'required|in:job_type,sector,project_type',
        ];
    }
}
