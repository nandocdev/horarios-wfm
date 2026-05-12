<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Events;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se actualiza un empleado.
 *
 * @module EmployeesModule
 *
 * @type Event
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class EmployeeUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Employee $employee
    ) {}
}
