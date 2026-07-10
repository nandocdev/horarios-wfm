<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklySchedule extends Model
{
    use HasFactory;

    protected $fillable = ['week_start_date', 'week_end_date', 'status', 'published_at'];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(WeeklyScheduleAssignment::class);
    }

    public function teamAssignments(): HasMany
    {
        return $this->hasMany(WeeklyTeamAssignment::class);
    }
}
