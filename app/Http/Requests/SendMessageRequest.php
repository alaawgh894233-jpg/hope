<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'       => 'required_without:attachment|string|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_without' => 'يجب إدخال نص الرسالة أو إرفاق ملف.',
            'body.max'              => 'الرسالة طويلة جداً (الحد الأقصى 5000 حرف).',
            'attachment.max'        => 'حجم الملف كبير جداً (الحد الأقصى 10MB).',
            'attachment.mimes'      => 'نوع الملف غير مدعوم.',
        ];
    }
}
