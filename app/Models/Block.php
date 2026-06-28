<?php

namespace App\Models;


class Block extends Model
{
    protected $fillable = ['blocker_id'];
    public function blockable() { return $this->morphTo(); }
}

// app/Models/User.php
