<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SendTrainingEndingReminders;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SendTrainingEndingReminders)
    ->dailyAt('08:00');

Schedule::command('insights:recalculate-salary')
    ->dailyAt('03:00');
// routes/console.php
Schedule::command('digest:send-weekly')->weeklyOn(0, '09:00'); // كل أحد الساعة 9
