<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'company_name'   => 'sometimes|string|max:255',
            'description'    => 'nullable|string',
            'website_url'    => 'nullable|url',
            'local_address'  => 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'sector'         => 'nullable|string|max:100',
            'category'       => 'nullable|string|max:100',
            'logo'           => 'nullable|image|max:2048',

            // ✅ أنواع الدعم اللي الشركة تقدمها
            'support_offers'   => 'nullable|array',
            'support_offers.*' => 'in:funding,mentoring,partnership',
        ];
    }
}
