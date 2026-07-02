<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentIntradayActivity extends Model
{
    protected $table = 'intraday_activities';

    protected $fillable = [
        'employee_id', 'activity_type_id', 'approved_period_id', 'time_range', 'notes',
    ];
}
