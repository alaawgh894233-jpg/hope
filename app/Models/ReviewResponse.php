<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewResponse extends Model
{
    use SoftDeletes;

    protected $fillable = ['review_id', 'responder_id', 'response'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}
