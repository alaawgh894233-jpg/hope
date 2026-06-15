<?php

namespace App\Http\Controllers;

use App\Services\PasswordService;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function __construct(
        protected PasswordService $service
    ) {}

    public function requestOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        return response()->json(
            $this->service->requestOtp($request->email)
        );
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric'
        ]);

        return response()->json(
            $this->service->verifyOtp(
                $request->email,
                $request->otp
            )
        );
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'new_password' => 'required|min:8|confirmed'
        ]);

        return response()->json(
            $this->service->resetPassword(
                $request->email,
                $request->new_password
            )
        );
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        return response()->json(
            $this->service->resendOtp($request->email)
        );
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|min:8|confirmed'
        ]);

        return response()->json(
            $this->service->changePassword(
                $request->user(),
                $request->current_password,
                $request->new_password
            )
        );
    }
}
