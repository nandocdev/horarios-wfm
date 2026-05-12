<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Events;

use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando un equipo es actualizado.
 */
class TeamUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Team $team
    ) {}
}
