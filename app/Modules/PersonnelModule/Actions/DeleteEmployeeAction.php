<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\Events\EmployeeDeleted;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Facades\DB;

final class DeleteEmployeeAction
{
    public function execute(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->delete();

            event(new EmployeeDeleted($employee));
        });
    }
}
