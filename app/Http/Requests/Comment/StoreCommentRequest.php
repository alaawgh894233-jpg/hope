<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
