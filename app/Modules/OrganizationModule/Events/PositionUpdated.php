<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Events;

use App\Modules\OrganizationModule\Models\Position;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando una posición es actualizada.
 */
class PositionUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Position $position
    ) {}
}
