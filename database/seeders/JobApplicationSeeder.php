<?php

namespace Database\Seeders;

use App\Models\HiringWorkflow;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\User;
use App\Models\WorkflowStage;
use Illuminate\Database\Seeder;

class JobApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $job   = JobPost::where('title', 'مطور Laravel')->first();
        $user1 = User::where('email', 'user1@test.com')->first();
        $user2 = User::where('email', 'user2@test.com')->first();

        if (!$job || !$user1) return;

        $workflow     = HiringWorkflow::first();
        $firstStage   = WorkflowStage::where('workflow_id', $workflow?->id)
            ->orderBy('order_index')->first();

        // طلب 1 — pending
        JobApplication::firstOrCreate(
            ['job_post_id' => $job->id, 'user_id' => $user1->id],
            [
                'cover_letter'     => 'أنا مطور Laravel بخبرة 3 سنوات وأرغب بالانضمام لفريقكم',
                'cv_snapshot'      => [
                    'skills'      => ['Laravel', 'PHP', 'MySQL'],
                    'experiences' => [['company' => 'شركة سابقة', 'years' => 2]],
                    'educations'  => [['degree' => 'بكالوريوس هندسة معلوماتية']],
                    'projects'    => [['name' => 'نظام إدارة متجر']],
                ],
                'status'           => 'pending',
                'workflow_id'      => $workflow?->id,
                'current_stage_id' => $firstStage?->id,
            ]
        );

        // طلب 2 — interview
        if ($user2) {
            JobApplication::firstOrCreate(
                ['job_post_id' => $job->id, 'user_id' => $user2->id],
                [
                    'cover_letter'     => 'مطورة Laravel بخبرة 4 سنوات',
                    'cv_snapshot'      => [
                        'skills'      => ['Laravel', 'Vue.js', 'Docker'],
                        'experiences' => [['company' => 'شركة تقنية', 'years' => 4]],
                        'educations'  => [['degree' => 'ماجستير علوم حاسوب']],
                        'projects'    => [['name' => 'منصة تعليمية']],
                    ],
                    'status'           => 'interview',
                    'workflow_id'      => $workflow?->id,
                    'current_stage_id' => $firstStage?->id,
                ]
            );
        }

        // تحديث عداد الطلبات
        $job->update(['applications_count' => JobApplication::where('job_post_id', $job->id)->count()]);
    }
}
