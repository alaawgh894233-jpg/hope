<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Skill;
use App\Models\Experience;
use App\Models\Education;
use App\Models\Project;
use App\Models\Certification;
use App\Models\Training;
use App\Models\Interest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CvTestSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ إنشاء مستخدم تجريبي
        $user = User::firstOrCreate(
            ['email' => 'alaa@example.com'],
            [
                'name' => 'Alaa Ghfary',
                'password' => Hash::make('password'),
            ]
        );

        // ✅ Profile - استخدام updateOrCreate بدلاً من firstOrCreate
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id], // ✅ البحث بـ user_id بدل phone
            [
                'full_name' => 'Alaa Ghfary',
                'headline' => 'Backend Laravel Developer',
                'summary' => 'Passionate backend developer with Laravel experience and API development skills',
                'phone' => '+963987654321',
                'linkedin' => 'https://linkedin.com/in/alaa-ghfary',
                'github' => 'https://github.com/alaa-ghfary',
                'city' => 'Damascus',
                'country' => 'Syria',
            ]
        );

        // ✅ حذف Skills القديمة لتجنب التكرار
        $user->skills()->delete();

        // ✅ Skills
        $skills = [
            ['name' => 'Laravel', 'type' => 'technical'],
            ['name' => 'PHP', 'type' => 'technical'],
            ['name' => 'MySQL', 'type' => 'technical'],
            ['name' => 'REST API', 'type' => 'technical'],
            ['name' => 'Postman', 'type' => 'tool'],
            ['name' => 'Git', 'type' => 'tool'],
            ['name' => 'English', 'type' => 'language'],
        ];

        foreach ($skills as $skill) {
            $user->skills()->create($skill);
        }

        // ✅ Experience - حذف القديمة
        $user->experiences()->delete();

        $user->experiences()->create([
            'company' => 'Tech Startup Syria',
            'position' => 'Backend Developer',
            'start_date' => '2023-01-01',
            'end_date' => '2025-01-01',
            'is_current' => false,
            'description' => "Developed RESTful APIs\nIntegrated payment gateways\nOptimized database queries",
            'technologies_used' => ['Laravel', 'PHP', 'MySQL', 'Redis'],
        ]);

        // ✅ Education - حذف القديمة
        $user->educations()->delete();

        $user->educations()->create([
            'institution' => 'Damascus University',
            'degree' => 'Bachelor',
            'field_of_study' => 'Information Engineering',
            'start_date' => '2020-09-09',
            'end_date' => '2025-09-09',
            'grade' => 90,
        ]);

        // ✅ Project - حذف القديمة
        $user->projects()->delete();

        $user->projects()->create([
            'title' => 'E-commerce Platform',
            'description' => 'Multi-vendor ecommerce backend built with Laravel',
            'link' => 'https://github.com/alaa-ghfary/ecommerce',
            'technologies' => ['Laravel', 'PHP', 'MySQL', 'Redis'],
        ]);

        // ✅ Certification - حذف القديمة
        $user->certifications()->delete();

        $user->certifications()->create([
            'name' => 'AWS Certified Cloud Practitioner',
            'issuer' => 'Amazon',
            'issued_at' => '2025-01-10',
            'expires_at' => '2028-01-10',
            'credential_id' => 'AWS-123456',
        ]);

        // ✅ Training - حذف القديمة
        $user->trainings()->delete();

        $user->trainings()->create([
            'title' => 'Laravel Advanced',
            'provider' => 'Udemy',
            'start_date' => '2024-01-01',
            'end_date' => '2025-01-01',
            'is_completed' => true,
        ]);

        // ✅ Interest - حذف القديمة
        $user->interests()->delete();

        $user->interests()->create([
            'name' => 'Football',
            'category' => 'sports',
            'level' => 3,
            'description' => 'Interested in sports',
        ]);

        $this->command->info('✅ Test data created successfully!');
        $this->command->info('📧 Email: alaa@example.com');
        $this->command->info('🔑 Password: password');
        $this->command->info('👤 User ID: ' . $user->id);
    }
}
