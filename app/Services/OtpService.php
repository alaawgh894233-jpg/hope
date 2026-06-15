<?php

namespace App\Services;

use App\Jobs\SendOtpJob;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    private int $ttl = 10;        // minutes
    private int $cooldown = 60;   // seconds

    private function key(string $type, string $identifier): string
    {
        return "otp:{$type}:{$identifier}";
    }

    private function cooldownKey(string $type, string $identifier): string
    {
        return "otp_cooldown:{$type}:{$identifier}";
    }


    public function send(string $type, string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'status' => 404,
                'message' => 'User not found'
            ];
        }


        if (Cache::has($this->key($type, $email))) {
            return [
                'status' => 429,
                'message' => 'OTP already sent, wait before requesting again'
            ];
        }

        $otp = random_int(100000, 999999);

        Cache::put(
            $this->key($type, $email),
            [
                'otp' => $otp,
                'email' => $email,
                'user_id' => $user->id,
                'verified' => false
            ],
            now()->addMinutes($this->ttl)
        );

        Cache::put(
            $this->cooldownKey($type, $email),
            true,
            now()->addSeconds($this->cooldown)
        );

        SendOtpJob::dispatch(
            $email,
            $user->name,
            $otp,
            $this->ttl
        );

        return [
            'status' => 200,
            'message' => 'OTP sent successfully',
            'expires_in' => $this->ttl . ' minutes'
        ];
    }


    public function verify(string $type, string $email, string $otp): array
    {
        $data = Cache::get($this->key($type, $email));

        if (!$data) {
            return [
                'status' => 400,
                'message' => 'OTP expired or not found'
            ];
        }

        if ($data['otp'] != $otp) {
            return [
                'status' => 400,
                'message' => 'Invalid OTP'
            ];
        }


        $data['verified'] = true;

        Cache::put(
            $this->key($type, $email),
            $data,
            now()->addMinutes($this->ttl)
        );

        return [
            'status' => 200,
            'message' => 'OTP verified successfully'
        ];
    }


    public function consume(string $type, string $email)
    {
        $data = Cache::get($this->key($type, $email));

        if (!$data || empty($data['verified'])) {
            return null;
        }

        Cache::forget($this->key($type, $email));
        Cache::forget($this->cooldownKey($type, $email));

        return $data;
    }


    public function resend(string $type, string $email): array
    {

        if (Cache::has($this->cooldownKey($type, $email))) {
            return [
                'status' => 429,
                'message' => 'Please wait before requesting another OTP'
            ];
        }


        if (Cache::has($this->key($type, $email))) {
            return [
                'status' => 429,
                'message' => 'OTP already active, check your email'
            ];
        }

        return $this->send($type, $email);
    }
}
