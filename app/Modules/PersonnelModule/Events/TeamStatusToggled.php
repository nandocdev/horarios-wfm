<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Events;

use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando el estado de un equipo cambia.
 */
class TeamStatusToggled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Team $team
    ) {}
}
