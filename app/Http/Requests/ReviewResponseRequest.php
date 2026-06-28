<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response' => 'required|string|min:20|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'response.required' => 'نص الرد مطلوب.',
            'response.min'      => 'الرد يجب أن يكون على الأقل 20 حرف.',
            'response.max'      => 'الرد طويل جداً (الحد الأقصى 2000 حرف).',
        ];
    }
}
