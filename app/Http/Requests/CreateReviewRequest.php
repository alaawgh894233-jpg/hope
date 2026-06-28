<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isApplicantToCompany = $this->input('type') === 'applicant_to_company';

        return [
            'type' => [
                'required',
                Rule::in(['applicant_to_company', 'company_to_applicant']),
            ],

            // التقييم العام (إلزامي)
            'overall_rating' => 'required|integer|min:1|max:5',

            // تقييمات الشركة (للمتقدم فقط)
            'work_environment_rating'     => $isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',
            'management_rating'           => $isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',
            'salary_benefits_rating'      => $isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',
            'career_growth_rating'        => $isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',
            'work_life_balance_rating'    => $isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',
            'interview_experience_rating' => $isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',

            // تقييمات المتقدم (للشركة فقط)
            'technical_skills_rating'  => !$isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',
            'communication_rating'     => !$isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',
            'professionalism_rating'   => !$isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',
            'reliability_rating'       => !$isApplicantToCompany ? 'nullable|integer|min:1|max:5' : 'prohibited',

            // النصوص
            'title'            => 'nullable|string|max:200',
            'pros'             => 'nullable|string|max:2000',
            'cons'             => 'nullable|string|max:2000',
            'advice'           => 'nullable|string|max:1000',
            'would_recommend'  => 'nullable|boolean',
            'is_anonymous'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'           => 'يجب تحديد نوع التقييم.',
            'overall_rating.required' => 'التقييم العام مطلوب.',
            'overall_rating.min'      => 'التقييم يجب أن يكون بين 1 و 5.',
            'overall_rating.max'      => 'التقييم يجب أن يكون بين 1 و 5.',
        ];
    }
}
