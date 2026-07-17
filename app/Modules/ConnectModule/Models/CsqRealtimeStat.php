<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Models;

use Illuminate\Database\Eloquent\Model;

class CsqRealtimeStat extends Model {
    protected $table = 'csq_realtime_stats';

    protected $fillable = [
        'csq_name',
        'calls_waiting',
        'longest_call_in_queue',
        'agents_logged_on',
        'agents_talking',
        'agents_ready',
        'agents_not_ready',
        'agents_after_call_work',
        'agents_reserved',
        'service_level_short_term',
        'service_level_long_term',
        'calls_abandoned_since_midnight',
        'calls_handled_since_midnight',
        'total_calls_since_midnight',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'service_level_short_term' => 'float',
        'service_level_long_term' => 'float',
        'calls_waiting' => 'integer',
        'longest_call_in_queue' => 'integer',
        'agents_logged_on' => 'integer',
        'agents_talking' => 'integer',
        'agents_ready' => 'integer',
        'agents_not_ready' => 'integer',
        'agents_after_call_work' => 'integer',
        'agents_reserved' => 'integer',
    ];
}
