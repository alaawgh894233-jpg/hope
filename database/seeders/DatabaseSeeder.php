<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,           // 1. Admin
            RoleSeeder::class,
            CvTestSeeder::class,
            CategorySeeder::class,        // 2. الفئات
            UserSeeder::class,            // 3. المستخدمين
            CompanySeeder::class,         // 4. الشركات (بعد المستخدمين)
            JobPostSeeder::class,         // 5. الوظائف (بعد الشركات والفئات)
            JobApplicationSeeder::class,  // 7. الطلبات (بعد الوظائف والـ Workflow)
            InterviewSeeder::class,       // 8. المقابلات (بعد الطلبات)
            ApplicationTrainingSeeder::class, // 9. التدريب (بعد الطلبات)
            StartupProjectSeeder::class,  // 10. المشاريع (بعد المستخدمين والشركات)
        ]);
    }
}
