<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentPollResponse extends Model {
    protected $table = 'poll_responses';

    protected $fillable = [
        'poll_id',
        'user_id',
        'answer',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function poll(): BelongsTo {
        return $this->belongsTo(EloquentPoll::class, 'poll_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo('App\Modules\CoreModule\Models\User', 'user_id');
    }
}
