<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'body',
        'type',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'read_at',
        'deleted_by_sender_at',
    ];

    protected $casts = [
        'read_at'               => 'datetime',
        'deleted_by_sender_at'  => 'datetime',
    ];

    // ==================
    // Relations
    // ==================

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ==================
    // Helpers
    // ==================

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isFromApplicant(): bool
    {
        return $this->sender_type === 'applicant';
    }

    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null;
    }
}
