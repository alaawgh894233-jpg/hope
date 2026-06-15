<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // 💼 أنواع الوظائف
            ['name' => 'Full Time',   'name_ar' => 'دوام كامل',   'type' => 'job_type'],
            ['name' => 'Part Time',   'name_ar' => 'دوام جزئي',   'type' => 'job_type'],
            ['name' => 'Contract',    'name_ar' => 'عقد',          'type' => 'job_type'],
            ['name' => 'Internship',  'name_ar' => 'تدريب',        'type' => 'job_type'],
            ['name' => 'Freelance',   'name_ar' => 'عمل حر',       'type' => 'job_type'],

            // 🏢 قطاعات الشركات
            ['name' => 'Technology',  'name_ar' => 'تقنية',        'type' => 'sector'],
            ['name' => 'Finance',     'name_ar' => 'مالية',        'type' => 'sector'],
            ['name' => 'Healthcare',  'name_ar' => 'صحة',          'type' => 'sector'],
            ['name' => 'Education',   'name_ar' => 'تعليم',        'type' => 'sector'],
            ['name' => 'Investment',  'name_ar' => 'استثمار',      'type' => 'sector'],
            ['name' => 'Consulting',  'name_ar' => 'استشارات',     'type' => 'sector'],
            ['name' => 'Marketing',   'name_ar' => 'تسويق',        'type' => 'sector'],
            ['name' => 'Retail',      'name_ar' => 'تجزئة',        'type' => 'sector'],

            // 🚀 تصنيفات المشاريع
            ['name' => 'Startup',     'name_ar' => 'ناشئة',        'type' => 'project_type'],
            ['name' => 'Social',      'name_ar' => 'اجتماعي',      'type' => 'project_type'],
            ['name' => 'Tech',        'name_ar' => 'تقني',         'type' => 'project_type'],
            ['name' => 'E-Commerce',  'name_ar' => 'تجارة إلكترونية', 'type' => 'project_type'],
            ['name' => 'Agriculture', 'name_ar' => 'زراعة',        'type' => 'project_type'],
            ['name' => 'Environment', 'name_ar' => 'بيئة',         'type' => 'project_type'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [...$cat, 'slug' => Str::slug($cat['name']), 'is_active' => true]
            );
        }
    }
}
