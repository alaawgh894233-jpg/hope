<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDeletionSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'scheduled_for',
        'is_deleted'
    ];
}
