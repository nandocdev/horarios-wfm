<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentReaction extends Model {
    protected $table = 'reactions';

    protected $fillable = [
        'shoutout_id',
        'user_id',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function shoutout(): BelongsTo {
        return $this->belongsTo(EloquentShoutout::class, 'shoutout_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo('App\Modules\CoreModule\Models\User', 'user_id');
    }
}
