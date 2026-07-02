<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent;

use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Traits\Auditable;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReactionModel extends Model
{
    use Auditable;

    protected $table = 'reactions';

    protected $fillable = [
        'shoutout_id', 'user_id', 'type', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shoutout(): BelongsTo
    {
        return $this->belongsTo(ShoutoutModel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
