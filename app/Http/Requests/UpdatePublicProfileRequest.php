<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_public'                      => 'nullable|boolean',
            'theme_color'                    => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'meta_title'                     => 'nullable|string|max:100',
            'meta_description'               => 'nullable|string|max:300',
            'visible_sections'               => 'nullable|array',
            'visible_sections.contact_info'  => 'nullable|boolean',
            'visible_sections.experience'    => 'nullable|boolean',
            'visible_sections.education'     => 'nullable|boolean',
            'visible_sections.skills'        => 'nullable|boolean',
            'visible_sections.projects'      => 'nullable|boolean',
            'visible_sections.certifications'=> 'nullable|boolean',
            'visible_sections.reviews'       => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'theme_color.regex' => 'لون المظهر يجب أن يكون بتنسيق HEX مثل #3B82F6.',
        ];
    }
}
