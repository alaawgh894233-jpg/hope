<?php

namespace Database\Seeders;

use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class InterviewSeeder extends Seeder
{
    public function run(): void
    {
        $user2       = User::where('email', 'user2@test.com')->first();
        $application = JobApplication::where('user_id', $user2?->id)
            ->where('status', 'interview')->first();

        if (!$application) return;

        Interview::firstOrCreate(
            ['job_application_id' => $application->id],
            [
                'scheduled_at' => now()->addDays(3),
                'type'         => 'online',
                'meeting_link' => 'https://meet.google.com/test-link',
                'notes'        => 'مقابلة تقنية + HR',
                'status'       => 'scheduled',
            ]
        );
    }
}
