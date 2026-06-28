<?php

namespace App\Console\Commands;

use App\Models\JobPost;
use App\Models\SalaryInsight;
use Illuminate\Console\Command;

class RecalculateSalaryInsights extends Command
{
    protected $signature = 'insights:recalculate-salary';
    protected $description = 'إعادة حساب متوسط الرواتب حسب الكاتيغوري والموقع';

    public function handle(): void
    {
        $rows = JobPost::query()
            ->whereNotNull('salary_min')
            ->whereNotNull('salary_max')
            ->select('category_id', 'location', 'type')
            ->selectRaw('AVG((salary_min + salary_max) / 2) as avg_salary')
            ->selectRaw('MIN(salary_min) as min_salary')
            ->selectRaw('MAX(salary_max) as max_salary')
            ->selectRaw('COUNT(*) as sample_size')
            ->groupBy('category_id', 'location', 'type')
            ->having('sample_size', '>=', 3) // خصوصية: ما تنعرض إنسايت لعينة صغيرة
            ->get();

        foreach ($rows as $row) {
            SalaryInsight::updateOrCreate(
                [
                    'category_id' => $row->category_id,
                    'location'    => $row->location,
                    'job_type'    => $row->type,
                ],
                [
                    'avg_salary'    => round($row->avg_salary),
                    'min_salary'    => $row->min_salary,
                    'max_salary'    => $row->max_salary,
                    'sample_size'   => $row->sample_size,
                    'calculated_at' => now(),
                ]
            );
        }

        $this->info("تم تحديث {$rows->count()} إنسايت.");
    }
}
