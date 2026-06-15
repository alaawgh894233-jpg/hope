<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'      => 'sometimes|string|max:100|unique:categories,name,' . $id,
            'name_ar'   => 'nullable|string|max:100',
            'type'      => 'sometimes|in:job_type,sector,project_type',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
