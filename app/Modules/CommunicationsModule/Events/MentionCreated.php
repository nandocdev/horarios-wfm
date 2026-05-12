<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Events;

use App\Modules\CommunicationsModule\Models\Mention;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se crea una mención.
 */
class MentionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Mention $mention
    ) {}
}
