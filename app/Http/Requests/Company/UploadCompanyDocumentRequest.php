<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UploadCompanyDocumentRequest extends FormRequest
{


    public function rules(): array
    {
        return [
            'type' => 'required|in:license,commercial_register,tax_document,identity,certificate,other',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ];
    }
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->role === 'company';
    }
}
