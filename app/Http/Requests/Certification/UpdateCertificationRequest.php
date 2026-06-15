<?php

namespace App\Http\Requests\Certification;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',

            'issuer' => 'nullable|string|max:255',

            'issued_at' => 'nullable|date',

            'expires_at' => 'nullable|date',

            'credential_id' => 'nullable|string|max:255',
        ];
    }
}
