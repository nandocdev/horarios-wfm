<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Events;

use App\Modules\OrganizationModule\Models\Directorate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se cambia el estado de una dirección.
 */
class DirectorateStatusToggled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Directorate $directorate
    ) {}
}
