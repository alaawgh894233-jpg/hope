<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->service->getUserNotifications($request->user()->id),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => ['count' => $this->service->unreadCount($request->user()->id)],
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $this->service->markAsRead($id, $request->user()->id);

        return response()->json(['status' => 'success', 'message' => 'تم تحديد الإشعار كمقروء.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->service->markAllAsRead($request->user()->id);

        return response()->json(['status' => 'success', 'message' => 'تم تحديد كل الإشعارات كمقروءة.']);
    }
}
