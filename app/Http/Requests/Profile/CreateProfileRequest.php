<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class CreateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'headline' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'full_name' => 'nullable|string',
            'linkedin' => 'nullable|url',
            'github' => 'nullable|url',
            'portfolio' => 'nullable|url',

            // هون التعديل
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ];
    }
}
