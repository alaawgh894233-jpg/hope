<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseNotificationService;

class FirebaseNotificationController extends Controller
{
    protected $fcm;

    public function __construct(FirebaseNotificationService $fcm)
    {
        $this->fcm = $fcm;
    }

    // حفظ توكن الجهاز
    public function saveToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);

        auth()->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['message' => 'تم حفظ التوكن بنجاح']);
    }

    // إرسال إشعار
    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body'  => 'required|string',
        ]);

        $token = auth()->user()->fcm_token;

        if (!$token) {
            return response()->json(['message' => 'لا يوجد توكن للمستخدم'], 400);
        }

        $this->fcm->sendToToken(
            $token,
            $request->title,
            $request->body,
            $request->data ?? []
        );

        return response()->json(['message' => 'تم إرسال الإشعار بنجاح']);
    }
}
