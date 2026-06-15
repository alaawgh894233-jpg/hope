<?php

namespace App\Http\Requests\Certification;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',

            'issuer' => 'nullable|string|max:255',

            'issued_at' => 'nullable|date',

            'expires_at' => 'nullable|date|after_or_equal:issued_at',

            'credential_id' => 'nullable|string|max:255',
        ];
    }
}
