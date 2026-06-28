<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Conversation extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'job_application_id',
        'applicant_id',
        'company_user_id',
        'status',
        'last_message',
        'last_message_at',
        'applicant_unread_count',
        'company_unread_count',
    ];
    protected $casts = [
        'last_message_at' => 'datetime',
    ];
    // ==================
    // Relations
    // ==================
    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }
    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }
    public function companyUser()
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
    // ==================
    // Scopes
    // ==================
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    public function scopeForUser($query, int $userId)
    {
        return $query->where('applicant_id', $userId)
            ->orWhere('company_user_id', $userId);
    }
    // ==================
    // Helpers
    // ==================
    public function getUnreadCountFor(int $userId): int
    {
        if ($userId === $this->applicant_id) {
            return $this->applicant_unread_count;
        }
        if ($userId === $this->company_user_id) {
            return $this->company_unread_count;
        }
        return 0;
    }
    public function markAsReadFor(int $userId): void
    {
        if ($userId === $this->applicant_id) {
            $this->update(['applicant_unread_count' => 0]);
        } elseif ($userId === $this->company_user_id) {
            $this->update(['company_unread_count' => 0]);
        }
    }
}
