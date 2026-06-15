<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_application_id' => ['required', 'exists:job_applications,id'],
            'scheduled_at' => ['required', 'date'],
            'type' => ['required', 'in:online,offline,phone'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url'],
            'notes' => ['nullable', 'string']
        ];
    }
}
