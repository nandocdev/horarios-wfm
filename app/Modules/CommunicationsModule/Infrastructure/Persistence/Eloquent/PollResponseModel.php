<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollResponseModel extends Model
{
    protected $table = 'poll_responses';

    protected $fillable = [
        'poll_id', 'user_id', 'answer',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(PollModel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
