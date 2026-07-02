<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentApprovedIntradayPeriod extends Model
{
    protected $table = 'approved_intraday_periods';

    protected $fillable = [
        'team_id', 'activity_definition_id', 'date',
        'start_time', 'end_time', 'max_slots', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'max_slots' => 'integer',
    ];
}
