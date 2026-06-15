<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class PasswordService
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    // 1️⃣ REQUEST OTP
    public function requestOtp(string $email): array
    {
        return $this->otpService->send('password_reset', $email);
    }

    // 2️⃣ VERIFY OTP
    public function verifyOtp(string $email, string $otp): array
    {
        $result = $this->otpService->verify('password_reset', $email, $otp);

        if ($result['status'] !== 200) {
            return $result;
        }

        // session verification flag
        cache()->put(
            "otp:password_reset:verified:$email",
            true,
            now()->addMinutes(10)
        );

        return [
            'status' => 200,
            'message' => 'OTP verified'
        ];
    }

    // 3️⃣ RESET PASSWORD (secured)
    public function resetPassword(string $email, string $newPassword): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'status' => 404,
                'message' => 'User not found'
            ];
        }

        if (!cache()->get("otp:password_reset:verified:$email")) {
            return [
                'status' => 403,
                'message' => 'OTP not verified'
            ];
        }

        // 🔥 مهم: منع نفس كلمة المرور القديمة
        if (Hash::check($newPassword, $user->password)) {
            return [
                'status' => 400,
                'message' => 'New password cannot be the same as the old password'
            ];
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        cache()->forget("otp:password_reset:verified:$email");
        $this->otpService->consume('password_reset', $email);

        return [
            'status' => 200,
            'message' => 'Password reset successfully'
        ];
    }
    // 🔐 CHANGE PASSWORD (authenticated user)
    public function changePassword($user, string $currentPassword, string $newPassword)
    {
        $key = 'change-password:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return [
                'status' => 429,
                'payload' => [
                    'message' => 'Too many attempts. Try again later.'
                ]
            ];
        }

        RateLimiter::hit($key, 60);

        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'status' => 400,
                'payload' => [
                    'message' => 'Current password is incorrect'
                ]
            ];
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        RateLimiter::clear($key);

        return [
            'status' => 200,
            'payload' => [
                'message' => 'Password changed successfully'
            ]
        ];
    }

    // 4️⃣ RESEND OTP
    public function resendOtp(string $email): array
    {
        return $this->otpService->resend('password_reset', $email);
    }
}
