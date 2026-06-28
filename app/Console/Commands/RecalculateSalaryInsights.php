<?php

namespace App\Console\Commands;

use App\Models\JobPost;
use Illuminate\Console\Command;

class RecalculateSalaryInsights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalculate-salary-insights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        JobPost::query()
            ->whereNotNull('salary_min')
            ->select('category_id', 'location', 'type')
            ->selectRaw('AVG(salary_min + salary_max)/2 as avg_salary')
            ->selectRaw('MIN(salary_min) as min_salary, MAX(salary_max) as max_salary')
            ->selectRaw('COUNT(*) as sample_size')
            ->groupBy('category_id', 'location', 'type')
            ->having('sample_size', '>=', 3) // خصوصية: ماتعرض إنسايت لعينة صغيرة
            ->get()
            ->each(fn($row) => SalaryInsight::updateOrCreate(
                ['category_id' => $row->category_id, 'location' => $row->location, 'job_type' => $row->type],
                ['avg_salary' => $row->avg_salary, 'min_salary' => $row->min_salary,
                    'max_salary' => $row->max_salary, 'sample_size' => $row->sample_size,
                    'calculated_at' => now()]
            ));
    }
}
