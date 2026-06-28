<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\JobApplication;
use App\Models\Message;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConversationService
{
    /**
     * إنشاء أو جلب محادثة لطلب وظيفي معين
     * يُستدعى عند قبول الطلب
     */
    public function findOrCreateForApplication(JobApplication $application, int $companyUserId): Conversation
    {
        return Conversation::firstOrCreate(
            ['job_application_id' => $application->id],
            [
                'applicant_id'    => $application->user_id,
                'company_user_id' => $companyUserId,
                'status'          => 'active',
            ]
        );
    }

    /**
     * إرسال رسالة
     */
    public function sendMessage(
        Conversation $conversation,
        int $senderId,
        string $body,
        string $type = 'text',
        ?array $attachment = null
    ): Message {
        return DB::transaction(function () use ($conversation, $senderId, $body, $type, $attachment) {

            $senderType = $senderId === $conversation->applicant_id ? 'applicant' : 'company';

            $message = Message::create([
                'conversation_id'  => $conversation->id,
                'sender_id'        => $senderId,
                'sender_type'      => $senderType,
                'body'             => $body,
                'type'             => $type,
                'attachment_path'  => $attachment['path'] ?? null,
                'attachment_name'  => $attachment['name'] ?? null,
                'attachment_type'  => $attachment['mime'] ?? null,
            ]);

            // تحديث آخر رسالة في المحادثة
            $conversation->update([
                'last_message'    => $this->truncateMessage($body),
                'last_message_at' => now(),
            ]);

            // زيادة عداد الرسائل غير المقروءة للطرف الآخر
            if ($senderType === 'applicant') {
                $conversation->increment('company_unread_count');
            } else {
                $conversation->increment('applicant_unread_count');
            }

            return $message;
        });
    }

    /**
     * رفع مرفق
     */
    public function uploadAttachment($file, int $conversationId): array
    {
        $path = $file->store("conversations/{$conversationId}/attachments", 'private');

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
        ];
    }

    /**
     * تعليم الرسائل كمقروءة
     */
    public function markAsRead(Conversation $conversation, int $userId): int
    {
        $senderType = $userId === $conversation->applicant_id ? 'company' : 'applicant';

        // تعليم الرسائل غير المقروءة من الطرف الآخر
        $count = Message::where('conversation_id', $conversation->id)
            ->where('sender_type', $senderType)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // إعادة تعيين العداد
        $conversation->markAsReadFor($userId);

        return $count;
    }

    /**
     * جلب محادثات المستخدم
     */
    public function getUserConversations(int $userId): LengthAwarePaginator
    {
        return Conversation::with([
            'jobApplication.jobPost',
            'applicant:id,name',
            'companyUser:id,name',
            'latestMessage',
        ])
            ->where(function ($q) use ($userId) {
                $q->where('applicant_id', $userId)
                    ->orWhere('company_user_id', $userId);
            })
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);
    }

    /**
     * جلب رسائل محادثة
     */
    public function getMessages(Conversation $conversation, int $perPage = 50): LengthAwarePaginator
    {
        return Message::with('sender:id,name')
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * إرسال رسالة نظام (تلقائية)
     */
    public function sendSystemMessage(Conversation $conversation, string $text): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $conversation->company_user_id,
            'sender_type'     => 'company',
            'body'            => $text,
            'type'            => 'system',
        ]);
    }

    /**
     * إغلاق المحادثة
     */
    public function closeConversation(Conversation $conversation): void
    {
        $conversation->update(['status' => 'closed']);
        $this->sendSystemMessage($conversation, 'تم إغلاق هذه المحادثة');
    }

    private function truncateMessage(string $message, int $length = 100): string
    {
        return strlen($message) > $length
            ? substr($message, 0, $length) . '...'
            : $message;
    }
}
