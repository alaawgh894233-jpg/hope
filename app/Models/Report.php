<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['reporter_id', 'reason', 'details'];

    public function reportable() { return $this->morphTo(); }
    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
}

