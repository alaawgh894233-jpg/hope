<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOnboarding extends Model
{
    protected $table = 'user_onboarding';

    protected $fillable = [
        'user_id',
        'user_type',
        'current_step',
        'total_steps',
        'completed_steps',
        'is_completed',
        'completed_at',
        'is_skipped',
        'skipped_at',
        'reminder_sent_at',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'is_completed'    => 'boolean',
        'is_skipped'      => 'boolean',
        'completed_at'    => 'datetime',
        'skipped_at'      => 'datetime',
        'reminder_sent_at'=> 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_steps == 0) return 0;

        return round(
            (count($this->completed_steps ?? []) / $this->total_steps) * 100,
            2
        );
    }

    public function completeStep(int $step): void
    {
        $steps = $this->completed_steps ?? [];

        if (!in_array($step, $steps)) {
            $steps[] = $step;
        }

        $this->completed_steps = $steps;

        $this->current_step = min($step + 1, $this->total_steps);

        if (count($steps) >= $this->total_steps) {
            $this->is_completed = true;
            $this->completed_at = now();
        }

        $this->save();
    }

    public static function applicantSteps(): array
    {
        return [
            1 => 'profile',
            2 => 'experiences',
            3 => 'skills',
            4 => 'education',
            5 => 'cv_file',
            6 => 'preferences',
        ];
    }
}
