<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        protected ConversationService $service
    ) {}

    /**
     * GET /conversations
     * جلب كل محادثات المستخدم
     */
    public function index(Request $request): JsonResponse
    {
        $conversations = $this->service->getUserConversations($request->user()->id);

        return response()->json([
            'status' => 'success',
            'data'   => $conversations,
        ]);
    }

    /**
     * GET /conversations/{conversation}
     * جلب محادثة مع رسائلها
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        // تعليم الرسائل كمقروءة
        $this->service->markAsRead($conversation, $request->user()->id);

        $messages = $this->service->getMessages($conversation);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'conversation' => $conversation->load([
                    'applicant:id,name',
                    'companyUser:id,name',
                    'jobApplication.jobPost:id,title',
                ]),
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * POST /conversations/{conversation}/messages
     * إرسال رسالة
     */
    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = $this->service->uploadAttachment(
                $request->file('attachment'),
                $conversation->id
            );
        }

        $message = $this->service->sendMessage(
            conversation: $conversation,
            senderId:     $request->user()->id,
            body:         $request->input('body'),
            type:         $attachment ? 'file' : 'text',
            attachment:   $attachment,
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إرسال الرسالة بنجاح',
            'data'    => $message->load('sender:id,name'),
        ], 201);
    }

    /**
     * POST /conversations/{conversation}/read
     * تعليم المحادثة كمقروءة
     */
    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $count = $this->service->markAsRead($conversation, $request->user()->id);

        return response()->json([
            'status'         => 'success',
            'messages_read'  => $count,
        ]);
    }

    /**
     * POST /conversations/{conversation}/close
     * إغلاق المحادثة (للشركة فقط)
     */
    public function close(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('close', $conversation);

        $this->service->closeConversation($conversation);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إغلاق المحادثة',
        ]);
    }

    /**
     * GET /conversations/unread-count
     * عدد الرسائل غير المقروءة الإجمالي
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $count = Conversation::where(function ($q) use ($userId) {
            $q->where('applicant_id', $userId)->where('applicant_unread_count', '>', 0);
        })->orWhere(function ($q) use ($userId) {
            $q->where('company_user_id', $userId)->where('company_unread_count', '>', 0);
        })->count();

        return response()->json([
            'status' => 'success',
            'data'   => ['unread_conversations' => $count],
        ]);
    }
}
