<?php

namespace App\Http\Controllers;

use App\Services\AccountService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $service
    ) {}

    public function requestDeletion(Request $request)
    {
        return response()->json(
            $this->service->requestDeletion($request->user())
        );
    }

    public function confirmDeletion(Request $request)
    {
        $request->validate([
            'otp' => 'required|string'
        ]);

        return response()->json(
            $this->service->confirmDeletion(
                $request->user(),
                $request->otp
            )
        );
    }

    public function restore(Request $request)
    {
        return response()->json(
            $this->service->restore($request->user())
        );
    }
}
