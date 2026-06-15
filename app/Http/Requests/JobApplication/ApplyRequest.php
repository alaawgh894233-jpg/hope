<?php

namespace App\Http\Requests\JobApplication;

use Illuminate\Foundation\Http\FormRequest;

class ApplyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cover_letter' => 'nullable|string|max:2000',
            'cv_file' => [
        'nullable',
        'file',
        'mimes:pdf,doc,docx',
        'max:5120'
    ],
        ];
    }
    public function authorize(): bool
    {
        return auth()->check();
    }
}
