<?php

namespace App\Http\Requests;

use App\Models\ApplicationWithdrawal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WithdrawApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_category' => [
                'required',
                Rule::in(array_keys(ApplicationWithdrawal::getReasonCategories())),
            ],
            'reason_details' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason_category.required' => 'يجب اختيار سبب الانسحاب.',
            'reason_category.in'       => 'سبب الانسحاب غير صحيح.',
            'reason_details.max'       => 'التفاصيل طويلة جداً (الحد الأقصى 1000 حرف).',
        ];
    }
}
