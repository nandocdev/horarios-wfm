<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Events;

use App\Modules\PersonnelModule\Models\Department;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se cambia el estado de un departamento.
 */
class DepartmentStatusToggled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Department $department
    ) {}
}
