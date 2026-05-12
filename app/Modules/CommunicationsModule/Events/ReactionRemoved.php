<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Events;

use App\Modules\CommunicationsModule\Models\Reaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se remueve una reacción.
 */
class ReactionRemoved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Reaction $reaction
    ) {}
}
