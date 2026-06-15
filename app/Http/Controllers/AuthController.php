<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $service
    ) {}

    public function register(RegisterRequest $request)
    {
        return response()->json(
            $this->service->register($request->validated())
        );
    }

    public function verifyRegisterOtp(VerifyOtpRequest $request)
    {
        return response()->json(
            $this->service->verifyOtp(
                $request->email,
                'register',
                $request->otp
            )
        );
    }

    public function resendOtp(ResendOtpRequest $request)
    {
        return response()->json(
            $this->service->resendOtp('register', $request->email)
        );
    }

    // Step 1: Login request -> send OTP (أو مباشرة للـ Admin)
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        return response()->json(
            $this->service->login($request->email, $request->password)
        );
    }

    // Step 2: Verify OTP -> actual login (للـ User والـ Company فقط)
    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string',
        ]);

        return response()->json(
            $this->service->verifyLoginOtp(
                $request->email,
                $request->otp
            )
        );
    }

    public function logout(Request $request)
    {
        return response()->json(
            $this->service->logout($request->user())
        );
    }
}
