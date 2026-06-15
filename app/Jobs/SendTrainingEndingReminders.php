<?php

namespace App\Jobs;

use App\Models\ApplicationTraining;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTrainingEndingReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        // يوم قبل الانتهاء
        ApplicationTraining::with('jobApplication.user.jobPost')
            ->whereDate('end_date', now()->addDay()->toDateString())
            ->where('result', 'in_progress')
            ->each(fn($training) => $notificationService->notifyTrainingEnding($training, false));

        // يوم الانتهاء
        ApplicationTraining::with('jobApplication.user.jobPost')
            ->whereDate('end_date', now()->toDateString())
            ->where('result', 'in_progress')
            ->each(fn($training) => $notificationService->notifyTrainingEnding($training, true));
    }
}
