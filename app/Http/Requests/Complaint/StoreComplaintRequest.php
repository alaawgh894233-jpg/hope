<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:post,comment,company,user',

            'reference_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');

                    $map = [
                        'post' => 'job_posts',
                        'comment' => 'comments',
                        'company' => 'companies',
                        'user' => 'users',
                    ];

                    if (!isset($map[$type])) {
                        $fail('Invalid type');
                    }

                    $exists = DB::table($map[$type])
                        ->where('id', $value)
                        ->exists();

                    if (!$exists) {
                        $fail("Reference not found in {$map[$type]}");
                    }
                }
            ],

            'message' => 'required|string|min:10',
        ];
    }
}
