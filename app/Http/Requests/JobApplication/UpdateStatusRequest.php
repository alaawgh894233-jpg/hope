<?php

namespace App\Http\Requests\JobApplication;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // ✅ validation على القيم المسموحة بس
            'status' => 'required|in:pending,interview,training,accepted,rejected'
        ];
    }
}
