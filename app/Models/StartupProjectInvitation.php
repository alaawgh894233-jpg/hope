<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StartupProjectInvitation extends Model
{
    protected $fillable = [
        'startup_project_id',
        'company_id',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(StartupProject::class, 'startup_project_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
