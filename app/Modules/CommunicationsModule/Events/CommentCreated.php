<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Events;

use App\Modules\CommunicationsModule\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se crea un comentario.
 */
class CommentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Comment $comment
    ) {}
}
