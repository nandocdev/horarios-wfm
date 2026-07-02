<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EloquentMention extends Model {
    protected $table = 'mentions';

    protected $fillable = [
        'mentioned_user_id',
        'mentioner_user_id',
        'mentionable_type',
        'mentionable_id',
        'context',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function mentionedUser(): BelongsTo {
        return $this->belongsTo('App\Modules\CoreModule\Models\User', 'mentioned_user_id');
    }

    public function mentionerUser(): BelongsTo {
        return $this->belongsTo('App\Modules\CoreModule\Models\User', 'mentioner_user_id');
    }

    public function mentionable(): MorphTo {
        return $this->morphTo();
    }
}
