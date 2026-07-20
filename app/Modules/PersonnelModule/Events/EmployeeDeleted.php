<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Events;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Employee $employee
    ) {}
}
