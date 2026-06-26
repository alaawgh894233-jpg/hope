<?php
// database/seeders/WorkflowTestSeeder.php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\JobPost;
use App\Models\JobApplication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowTestSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1) Workflow ──────────────────────────────────
        $workflowId = DB::table('hiring_workflows')->insertGetId([
            'company_id' => 1,
            'name'       => 'Standard Hiring Process',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── 2) Stages بترتيب واضح ────────────────────────
        $stages = [
            [
                'workflow_id'        => $workflowId,
                'name'               => 'Applied',
                'type'               => 'applied',
                'order_index'        => 1,
                'requires_approval'  => false,
                'is_final'           => false,
                'final_status'       => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'workflow_id'        => $workflowId,
                'name'               => 'Screening',
                'type'               => 'screening',
                'order_index'        => 2,
                'requires_approval'  => false,
                'is_final'           => false,
                'final_status'       => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'workflow_id'        => $workflowId,
                'name'               => 'Interview',
                'type'               => 'interview',
                'order_index'        => 3,
                'requires_approval'  => true,
                'is_final'           => false,
                'final_status'       => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'workflow_id'        => $workflowId,
                'name'               => 'Training',
                'type'               => 'training',
                'order_index'        => 4,
                'requires_approval'  => false,
                'is_final'           => false,
                'final_status'       => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'workflow_id'        => $workflowId,
                'name'               => 'Hired',
                'type'               => 'final_accept',
                'order_index'        => 5,
                'requires_approval'  => false,
                'is_final'           => true,
                'final_status'       => 'accepted',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'workflow_id'        => $workflowId,
                'name'               => 'Rejected',
                'type'               => 'final_reject',
                'order_index'        => 5,
                'requires_approval'  => false,
                'is_final'           => true,
                'final_status'       => 'rejected',
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ];

        DB::table('workflow_stages')->insert($stages);

        $stageIds = DB::table('workflow_stages')
            ->where('workflow_id', $workflowId)
            ->orderBy('order_index')
            ->pluck('id', 'name');

        // ── 3) Rules ─────────────────────────────────────
        DB::table('workflow_rules')->insert([
            [
                'workflow_id'     => $workflowId,
                'name'            => 'Auto reject low score',
                'field'           => 'ats_score',
                'operator'        => '<',
                'value'           => '40',
                'action'          => 'move_to_stage',
                'score_weight'    => 0,
                'priority'        => 1,
                'group_logic'     => 'AND',
                'target_stage_id' => $stageIds['Rejected'] ?? null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'workflow_id'     => $workflowId,
                'name'            => 'Auto advance high score',
                'field'           => 'ats_score',
                'operator'        => '>=',
                'value'           => '80',
                'action'          => 'move_to_stage',
                'score_weight'    => 0,
                'priority'        => 2,
                'group_logic'     => 'AND',
                'target_stage_id' => $stageIds['Interview'] ?? null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // ── 4) Job Application ───────────────────────────
        $applicationId = DB::table('job_applications')->insertGetId([
            'job_post_id'      => 1,
            'user_id'          => 1,
            'workflow_id'      => $workflowId,
            'current_stage_id' => $stageIds['Applied'],
            'status'           => 'pending',
            'cover_letter'     => 'Test application',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->command->info("✅ Created:");
        $this->command->info("   Workflow ID: {$workflowId}");
        $this->command->info("   Application ID: {$applicationId}");
        $this->command->info("   Stages: " . implode(', ', $stageIds->keys()->toArray()));
        $this->command->table(
            ['Stage', 'ID'],
            $stageIds->map(fn($id, $name) => [$name, $id])->values()->toArray()
        );
    }
}
