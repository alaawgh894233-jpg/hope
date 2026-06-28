<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => 'required|string|max:100',
            'criteria'          => 'required|array',
            'criteria.keywords' => 'nullable|array|max:10',
            'criteria.keywords.*' => 'string|max:50',
            'criteria.location' => 'nullable|string|max:100',
            'criteria.job_type' => 'nullable|array',
            'criteria.job_type.*' => Rule::in(['full_time', 'part_time', 'contract', 'freelance', 'internship']),
            'criteria.salary_min' => 'nullable|integer|min:0',
            'criteria.salary_max' => 'nullable|integer|min:0|gte:criteria.salary_min',
            'criteria.remote'     => 'nullable|boolean',
            'criteria.categories' => 'nullable|array',
            'criteria.categories.*' => 'integer|exists:categories,id',
            'frequency'         => Rule::in(['instantly', 'daily', 'weekly']),
            'notify_email'      => 'nullable|boolean',
            'notify_push'       => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'اسم التنبيه مطلوب.',
            'criteria.required'      => 'يجب تحديد معايير البحث.',
            'frequency.in'           => 'تكرار التنبيه غير صحيح.',
        ];
    }
}
