<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent;

use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Traits\Auditable;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MentionModel extends Model
{
    use Auditable;

    protected $table = 'mentions';

    protected $fillable = [
        'mentioned_user_id', 'mentioner_user_id', 'mentionable_type',
        'mentionable_id', 'context', 'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    public function mentionerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioner_user_id');
    }

    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }
}
