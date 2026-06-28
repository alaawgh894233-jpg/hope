<?php

namespace App\Services;

use App\Models\ProfileCompletion;
use App\Models\User;

class ProfileCompletionService
{
    /**
     * حساب وتحديث نسبة اكتمال البروفايل
     */
    public function calculate(User $user): ProfileCompletion
    {
        $profile = $user->profile;

        $sections = [
            'basic_info' => [
                'completed' => $this->checkBasicInfo($profile),
                'weight'    => 20,
                'label'     => 'المعلومات الأساسية',
                'icon'      => 'user',
            ],
            'photo' => [
                'completed' => !empty($profile?->profile_image),
                'weight'    => 10,
                'label'     => 'الصورة الشخصية',
                'icon'      => 'camera',
            ],
            'summary' => [
                'completed' => !empty($profile?->summary),
                'weight'    => 10,
                'label'     => 'الملخص المهني',
                'icon'      => 'document-text',
            ],
            'experience' => [
                'completed' => $user->experiences()->exists(),
                'weight'    => 20,
                'label'     => 'الخبرات العملية',
                'icon'      => 'briefcase',
            ],
            'education' => [
                'completed' => $user->educations()->exists(),
                'weight'    => 10,
                'label'     => 'التعليم',
                'icon'      => 'academic-cap',
            ],
            'skills' => [
                'completed' => $user->skills()->exists(),
                'weight'    => 15,
                'label'     => 'المهارات',
                'icon'      => 'sparkles',
            ],
            'cv_file' => [
                'completed' => !empty($profile?->cv_file),
                'weight'    => 10,
                'label'     => 'السيرة الذاتية',
                'icon'      => 'paper-clip',
            ],
            'certifications' => [
                'completed' => $user->certifications()->exists(),
                'weight'    => 5,
                'label'     => 'الشهادات والدورات',
                'icon'      => 'badge-check',
            ],
        ];

        // حساب النسبة الإجمالية
        $totalPoints = 0;
        foreach ($sections as $key => $section) {
            if ($section['completed']) {
                $totalPoints += $section['weight'];
                $sections[$key]['points'] = $section['weight'];
            } else {
                $sections[$key]['points'] = 0;
            }
        }

        $completionData = [
            'percentage'          => $totalPoints,
            'sections'            => $sections,
            'has_basic_info'      => $sections['basic_info']['completed'],
            'has_photo'           => $sections['photo']['completed'],
            'has_summary'         => $sections['summary']['completed'],
            'has_experience'      => $sections['experience']['completed'],
            'has_education'       => $sections['education']['completed'],
            'has_skills'          => $sections['skills']['completed'],
            'has_cv_file'         => $sections['cv_file']['completed'],
            'has_certifications'  => $sections['certifications']['completed'],
            'last_calculated_at'  => now(),
        ];

        return ProfileCompletion::updateOrCreate(
            ['user_id' => $user->id],
            $completionData
        );
    }

    /**
     * التحقق من المعلومات الأساسية
     */
    protected function checkBasicInfo($profile): bool
    {
        if (!$profile) return false;

        return !empty($profile->full_name)
            && !empty($profile->headline)
            && !empty($profile->phone)
            && !empty($profile->country);
    }

    /**
     * الحصول على نسبة الاكتمال الحالية (بدون إعادة الحساب)
     */
    public function getPercentage(User $user): int
    {
        return ProfileCompletion::where('user_id', $user->id)->value('percentage') ?? 0;
    }

    /**
     * الحصول على الأقسام الناقصة (للتشجيع على الإكمال)
     */
    public function getMissingSections(User $user): array
    {
        $completion = ProfileCompletion::where('user_id', $user->id)->first();

        if (!$completion) {
            $completion = $this->calculate($user);
        }

        return $completion->getMissingSections();
    }

    /**
     * الحصول على التوصيات الذكية
     */
    public function getRecommendations(User $user): array
    {
        $missing = $this->getMissingSections($user);
        $recommendations = [];

        foreach ($missing as $section) {
            $recommendations[] = [
                'section'  => $section['key'],
                'label'    => $section['label'],
                'weight'   => $section['weight'],
                'message'  => $this->getRecommendationMessage($section['key']),
                'priority' => $section['weight'] >= 15 ? 'high' : ($section['weight'] >= 10 ? 'medium' : 'low'),
            ];
        }

        // ترتيب حسب الأهمية
        usort($recommendations, fn($a, $b) => $b['weight'] - $a['weight']);

        return $recommendations;
    }

    private function getRecommendationMessage(string $section): string
    {
        return match($section) {
            'basic_info'     => 'أضف اسمك ومسماك الوظيفي لتحسين ظهورك في البحث',
            'photo'          => 'الملفات الشخصية مع صورة تحصل على 14x مشاهدات أكثر',
            'summary'        => 'اكتب ملخصاً يبرز خبراتك ومهاراتك',
            'experience'     => 'أضف خبراتك العملية لزيادة فرص القبول',
            'education'      => 'أضف شهاداتك الأكاديمية',
            'skills'         => 'المهارات تساعد الشركات في العثور عليك',
            'cv_file'        => 'ارفع سيرتك الذاتية للتقديم الفوري',
            'certifications' => 'الشهادات والدورات تُضاف قيمة لملفك',
            default          => 'أكمل هذا القسم لتحسين ملفك الشخصي',
        };
    }
}
