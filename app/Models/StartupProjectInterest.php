<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StartupProjectInterest extends Model
{
    use HasFactory;

    protected $table = 'startup_project_interests';

    protected $fillable = [
        'startup_project_id', 'company_id', 'support_type',
        'message', 'funding_amount', 'status', 'details_shared',
    ];

    protected $casts = [
        'funding_amount' => 'decimal:2',
        'details_shared' => 'boolean',
    ];

    public function project() { return $this->belongsTo(StartupProject::class, 'startup_project_id'); }
    public function company() { return $this->belongsTo(Company::class); }
}
