<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'email' => [
                'required',
                'email',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'

            ],

            // company fields
            'company_name' => 'required_if:role,company',

            'description' => 'nullable|string',

            'website_url' => 'nullable|url',

            'local_address' => 'required_with:company_name|string',

            'phone' => 'required_with:company_name|string',
        ];
    }
}
