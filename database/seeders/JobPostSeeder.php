<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobPost;
use App\Models\Category;
use Illuminate\Database\Seeder;

class JobPostSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('company_name', 'شركة التقنية المتقدمة')->first();
        if (!$company) return;

        $job1 = JobPost::firstOrCreate(
            ['title' => 'مطور Laravel', 'company_id' => $company->id],
            [
                'created_by'   => $company->user_id,
                'description'  => 'نبحث عن مطور Laravel متمرس لبناء APIs',
                'location'     => 'دمشق',
                'is_remote'    => true,
                'salary_range' => '1000-2000 USD',
                'type'         => 'full_time',
                'status'       => 'published',
                'skills'       => ['laravel', 'php', 'mysql'],
                'tags'         => ['#tech', '#backend', '#syria'],
                'expires_at'   => now()->addMonths(2),
            ]
        );

        $job2 = JobPost::firstOrCreate(
            ['title' => 'مصمم UI/UX', 'company_id' => $company->id],
            [
                'created_by'   => $company->user_id,
                'description'  => 'نبحث عن مصمم واجهات مستخدم مبدع',
                'location'     => 'دمشق',
                'is_remote'    => false,
                'salary_range' => '800-1500 USD',
                'type'         => 'full_time',
                'status'       => 'published',
                'skills'       => ['figma', 'adobe xd', 'ui design'],
                'tags'         => ['#design', '#ux', '#syria'],
                'expires_at'   => now()->addMonths(2),
            ]
        );

        // ✅ ربط بالفئات
        $techCategory = Category::where('slug', 'technology')->first();
        if ($techCategory) {
            $job1->categories()->syncWithoutDetaching([$techCategory->id]);
            $job2->categories()->syncWithoutDetaching([$techCategory->id]);
        }
    }
}
