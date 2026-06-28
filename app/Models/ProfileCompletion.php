<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'percentage',
        'sections',
        'has_basic_info',
        'has_photo',
        'has_summary',
        'has_experience',
        'has_education',
        'has_skills',
        'has_cv_file',
        'has_certifications',
        'has_projects',
        'last_calculated_at',
    ];

    protected $casts = [
        'sections'          => 'array',
        'has_basic_info'    => 'boolean',
        'has_photo'         => 'boolean',
        'has_summary'       => 'boolean',
        'has_experience'    => 'boolean',
        'has_education'     => 'boolean',
        'has_skills'        => 'boolean',
        'has_cv_file'       => 'boolean',
        'has_certifications'=> 'boolean',
        'has_projects'      => 'boolean',
        'last_calculated_at'=> 'datetime',
    ];

    /**
     * أوزان كل قسم (مجموعها 100)
     */
    public static array $sectionWeights = [
        'basic_info'     => 20,
        'photo'          => 10,
        'summary'        => 10,
        'experience'     => 20,
        'education'      => 10,
        'skills'         => 15,
        'cv_file'        => 10,
        'certifications' => 5,
    ];

    // ==================
    // Relations
    // ==================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==================
    // Helpers
    // ==================

    public function getMissingSections(): array
    {
        $missing = [];

        if (!$this->has_basic_info)    $missing[] = ['key' => 'basic_info',    'label' => 'المعلومات الأساسية',    'weight' => 20];
        if (!$this->has_photo)         $missing[] = ['key' => 'photo',         'label' => 'الصورة الشخصية',        'weight' => 10];
        if (!$this->has_summary)       $missing[] = ['key' => 'summary',       'label' => 'الملخص المهني',         'weight' => 10];
        if (!$this->has_experience)    $missing[] = ['key' => 'experience',    'label' => 'الخبرات العملية',       'weight' => 20];
        if (!$this->has_education)     $missing[] = ['key' => 'education',     'label' => 'التعليم',               'weight' => 10];
        if (!$this->has_skills)        $missing[] = ['key' => 'skills',        'label' => 'المهارات',              'weight' => 15];
        if (!$this->has_cv_file)       $missing[] = ['key' => 'cv_file',       'label' => 'السيرة الذاتية',        'weight' => 10];
        if (!$this->has_certifications)$missing[] = ['key' => 'certifications','label' => 'الشهادات والدورات',     'weight' => 5];

        return $missing;
    }

    public function getLevel(): string
    {
        return match(true) {
            $this->percentage >= 90 => 'expert',
            $this->percentage >= 70 => 'advanced',
            $this->percentage >= 50 => 'intermediate',
            $this->percentage >= 30 => 'beginner',
            default                 => 'incomplete',
        };
    }

    public function getLevelLabel(): string
    {
        return match($this->getLevel()) {
            'expert'       => 'خبير',
            'advanced'     => 'متقدم',
            'intermediate' => 'متوسط',
            'beginner'     => 'مبتدئ',
            default        => 'غير مكتمل',
        };
    }
}
