<?php

namespace App\Http\Requests\JobPost;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'        => 'sometimes|string|max:255',
            'description'  => 'sometimes|string',
            'location'     => 'nullable|string',
            'is_remote'    => 'boolean',
            'salary_range' => 'nullable|string',
            'type'         => 'sometimes|in:full_time,part_time,contract,internship,freelance',
            'status'       => 'nullable|in:draft,published,closed',
            'skills'       => 'nullable|array',
            'skills.*'     => 'string|max:50',
            'tags'         => 'nullable|array',
            'tags.*'       => 'string|max:50',
            'expires_at'     => 'nullable|date|after:today',
            'category_ids'   => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ];
    }
}
