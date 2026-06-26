<?php

namespace Database\Seeders;

use App\Models\ApplicationTraining;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationTrainingSeeder extends Seeder
{
    public function run(): void
    {
        $user3 = User::where('email', 'user3@test.com')->first();
        $job   = JobPost::where('title', 'مصمم UI/UX')->first();

        if (!$user3 || !$job) return;

        // أنشئ طلب للـ user3 إذا ما موجود
        $application = JobApplication::firstOrCreate(
            ['job_post_id' => $job->id, 'user_id' => $user3->id],
            [
                'cover_letter' => 'مصمم UX بخبرة 3 سنوات',
                'cv_snapshot'  => ['skills' => ['Figma', 'Adobe XD']],
                'status'       => 'training',
            ]
        );

        ApplicationTraining::firstOrCreate(
            ['job_application_id' => $application->id],
            [
                'start_date' => now()->subDays(10),
                'end_date'   => now()->addDays(20),
                'notes'      => 'تدريب على أدوات التصميم الداخلية',
                'result'     => 'in_progress',
            ]
        );
    }
}
