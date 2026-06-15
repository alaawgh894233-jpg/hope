<?php


namespace App\Http\Requests\Reactions;
use Illuminate\Foundation\Http\FormRequest;

class ReactPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:like,love,support,insightful'
        ];
    }
}
