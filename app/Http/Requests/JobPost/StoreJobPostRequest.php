<?php

namespace App\Http\Requests\JobPost;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobPostRequest extends FormRequest
{
    // ✅ كانت ناقصة
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->company !== null;
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'location'     => 'nullable|string',
            'is_remote'    => 'boolean',
            'salary_range' => 'nullable|string',
            'type'         => 'required|in:full_time,part_time,contract,internship,freelance',
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
