<?php

namespace App\Console\Commands;

use App\Mail\WeeklyJobDigestMail;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

// app/Console/Commands/SendWeeklyJobDigest.php
class SendWeeklyJobDigest extends Command
{
    protected $signature = 'digest:send-weekly {--user=}';
    // 👈 لو حطيت --user=5 رح يبعت لهداك اليوزر لحاله فقط (تست)

    public function handle()
    {
        $query = User::where('role', 'user')->whereHas('profile');

        if ($userId = $this->option('user')) {
            $query->where('id', $userId); // ✅ هون التست لحالك
        }

        $query->chunk(50, function ($users) {
            foreach ($users as $user) {
                $matchedJobs = $this->matchJobsFor($user);
                if ($matchedJobs->isNotEmpty()) {
                    Mail::to($user)->queue(new WeeklyJobDigestMail($user, $matchedJobs));
                }
            }
        });

        $this->info('Digest sent.');
    }

    private function matchJobsFor(User $user)
    {
        $interestCategoryIds = $user->interests->pluck('category_id'); // حسب جدول interests عندك
        return JobPost::where('status', 'published')
            ->where('created_at', '>=', now()->subWeek())
            ->whereIn('category_id', $interestCategoryIds)
            ->when($user->profile?->city, fn($q) => $q->where('location', 'like', "%{$user->profile->city}%"))
            ->excludingBlockedBy($user)
            ->limit(10)
            ->get();
    }
}
