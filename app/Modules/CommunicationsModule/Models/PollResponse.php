<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Models;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para Respuestas de Encuestas.
 */
class PollResponse extends Model
{
    protected $fillable = [
        'poll_id',
        'user_id',
        'answer',
    ];

    /**
     * Encuesta padre.
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * Usuario que respondió.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
