<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataExportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'file_path',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ✅ تشيك إذا لسا الملف صالح للتحميل
    public function isDownloadable(): bool
    {
        return $this->status === 'ready'
            && $this->file_path
            && $this->expires_at
            && $this->expires_at->isFuture();
    }
}
