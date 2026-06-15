<?php

namespace App\Services;

use App\Models\AccountDeletionSchedule;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function register(array $data): array
    {
        // ❌ منع تسجيل Admin عبر API
        if (!empty($data['role']) && $data['role'] === 'admin') {
            return [
                'status' => 403,
                'message' => 'Not allowed'
            ];
        }

        return DB::transaction(function () use ($data) {

            $isCompany = !empty($data['company_name']);

            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => $isCompany ? 'company' : 'user',
            ]);

            $company = null;

            if ($isCompany) {
                $company = Company::create([
                    'user_id'       => $user->id,
                    'company_name'  => $data['company_name'],
                    'description'   => $data['description'] ?? null,
                    'website_url'   => $data['website_url'] ?? null,
                    'local_address' => $data['local_address'] ?? null,
                    'phone'         => $data['phone'] ?? null,
                    'logo'          => $data['logo'] ?? null,
                    'status'        => 'pending',
                ]);
            }

            AuditService::log(
                $user,
                'register',
                'User',
                $user->id
            );

            return $this->otpService->send(
                'register',
                $user->email
            );
        });
    }

    public function verifyOtp(string $email, string $type, string $otp): array
    {
        $result = $this->otpService->verify($type, $email, $otp);

        if ($result['status'] !== 200) {
            return $result;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'status'  => 404,
                'message' => 'User not found'
            ];
        }

        $user->update([
            'email_verified_at' => now()
        ]);

        $this->otpService->consume($type, $email);

        AuditService::log(
            $user,
            'verify_email',
            'User',
            $user->id
        );

        return [
            'status'  => 200,
            'message' => 'Verified successfully',
            'token'   => $user->createToken('auth_token')->plainTextToken,
            'user'    => [
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        ];
    }

    // STEP 1: login -> send OTP only (أو مباشرة للـ Admin)
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return [
                'status'  => 401,
                'message' => 'Invalid credentials'
            ];
        }
        if ($user->banned_at) {
            return [
                'status'  => 403,
                'message' => 'Your account has been banned',
                'reason'  => $user->ban_reason,
            ];
        }


        // ✅ Admin — token مباشرة بدون OTP
        if ($user->role === 'admin') {
            AuditService::log($user, 'login', 'User', $user->id);

            return [
                'status'  => 200,
                'message' => 'Login success',
                'token'   => $user->createToken('auth_token')->plainTextToken,
                'user'    => [
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ]
            ];
        }

        // ✅ Company — لازم تكون approved
        if ($user->role === 'company') {
            if (!$user->company || $user->company->status !== 'approved') {
                return [
                    'status'  => 403,
                    'message' => 'Company waiting approval'
                ];
            }
        }

        // 🔥 إلغاء جدولة الحذف إذا موجودة
        $schedule = AccountDeletionSchedule::where('user_id', $user->id)->first();

        if ($schedule) {
            $schedule->delete();

            return [
                'status'  => 200,
                'message' => 'Account restored successfully. You can login now.'
            ];
        }

        // ✅ User / Company — OTP flow
        $this->otpService->send('login', $email);

        return [
            'status'  => 202,
            'message' => 'OTP sent'
        ];
    }

    // STEP 2: verify OTP and complete login
    public function verifyLoginOtp(string $email, string $otp): array
    {
        $verify = $this->otpService->verify('login', $email, $otp);

        if ($verify['status'] !== 200) {
            return $verify;
        }

        $data = $this->otpService->consume('login', $email);

        if (!$data) {
            return [
                'status'  => 400,
                'message' => 'OTP expired or invalid session'
            ];
        }

        $user = User::find($data['user_id']);

        if (!$user) {
            return [
                'status'  => 404,
                'message' => 'User not found'
            ];
        }

        AuditService::log($user, 'login', 'User', $user->id);

        return [
            'status'  => 200,
            'message' => 'Login success',
            'token'   => $user->createToken('auth_token')->plainTextToken,
            'user'    => [
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        ];
    }

    public function logout($user): array
    {
        AuditService::log($user, 'logout', 'User', $user->id);

        $user->currentAccessToken()->delete();

        return [
            'status'  => 200,
            'message' => 'Logged out'
        ];
    }

    public function resendOtp(string $type, string $email): array
    {
        return $this->otpService->resend($type, $email);
    }
}
