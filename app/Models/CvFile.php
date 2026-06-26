<?php
// app/Models/CvFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CvFile extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'stored_path',
        'disk',
        'size',
        'mime_type',
        'extracted_cv',
        'improved_cv',
        'is_confirmed',
    ];

    protected $casts = [
        'extracted_cv' => 'array',
        'improved_cv'  => 'array',
        'is_confirmed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDownloadUrl(): string
    {
        return Storage::disk($this->disk)->url($this->stored_path);
    }

    /**
     * المسار الكامل على السيرفر
     */
    /**
     * ✅ رابط مباشر للملف (بما أنه في public)
     */
    public function getPublicUrl(): string
    {
        if (empty($this->stored_path)) return '';

        return url('cv_files/' . $this->stored_path);
    }

}
