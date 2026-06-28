<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Block extends Model
{
    protected $fillable = [
        'blocker_id',
        'blockable_type',
        'blockable_id',
    ];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }
}
