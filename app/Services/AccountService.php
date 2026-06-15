<?php

namespace App\Services;

use App\Mail\AccountDeletionOtpMail;
use App\Mail\AccountDeletionScheduledMail;
use App\Mail\AccountRestoredMail;
use App\Models\AccountDeletionSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AccountService
{
    private int $otpTtl = 10;
    private int $cooldown = 60;
    private int $deleteDelayDays = 7;

    private function otpKey($userId)
    {
        return "acc:otp:$userId";
    }

    private function cooldownKey($userId)
    {
        return "acc:cooldown:$userId";
    }

    // 1️⃣ REQUEST DELETION
    public function requestDeletion(User $user)
    {
        if (Cache::has($this->cooldownKey($user->id))) {
            return [
                'status' => 429,
                'message' => 'Please wait before retrying'
            ];
        }

        $otp = random_int(100000, 999999);

        Cache::put(
            $this->otpKey($user->id),
            [
                'otp' => $otp,
                'attempts' => 0
            ],
            now()->addMinutes($this->otpTtl)
        );

        Cache::put(
            $this->cooldownKey($user->id),
            true,
            now()->addSeconds($this->cooldown)
        );

        Mail::to($user->email)->queue(
            new AccountDeletionOtpMail($user->name, $otp)
        );

        return [
            'status' => 200,
            'message' => 'OTP sent'
        ];
    }

    // 2️⃣ CONFIRM DELETION
    public function confirmDeletion(User $user, string $otp)
    {
        return DB::transaction(function () use ($user, $otp) {

            $cache = Cache::get($this->otpKey($user->id));

            if (!$cache) {
                return [
                    'status' => 400,
                    'message' => 'OTP expired'
                ];
            }

            if ($cache['attempts'] >= 3) {
                Cache::forget($this->otpKey($user->id));

                return [
                    'status' => 429,
                    'message' => 'Too many attempts'
                ];
            }

            if ($cache['otp'] != $otp) {
                $cache['attempts']++;

                Cache::put(
                    $this->otpKey($user->id),
                    $cache,
                    now()->addMinutes($this->otpTtl)
                );

                return [
                    'status' => 400,
                    'message' => 'Invalid OTP'
                ];
            }

            Cache::forget($this->otpKey($user->id));

            $date = now()->addDays($this->deleteDelayDays);

            AccountDeletionSchedule::updateOrCreate(
                ['user_id' => $user->id],
                ['scheduled_for' => $date]
            );

            Mail::to($user->email)->queue(
                new AccountDeletionScheduledMail(
                    $user->name,
                    $date->toDateString()
                )
            );

            return [
                'status' => 200,
                'message' => 'Account scheduled for deletion'
            ];
        });
    }

    // 3️⃣ RESTORE ACCOUNT
    public function restore(User $user)
    {
        $schedule = AccountDeletionSchedule::where('user_id', $user->id)->first();

        if (!$schedule) {
            return [
                'status' => 404,
                'message' => 'No deletion request found'
            ];
        }

        $schedule->delete();

        Cache::forget($this->otpKey($user->id));
        Cache::forget($this->cooldownKey($user->id));

        Mail::to($user->email)->queue(
            new AccountRestoredMail($user->name)
        );

        return [
            'status' => 200,
            'message' => 'Account restored successfully'
        ];
    }
}
