<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Observers;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Employee.
 * Solo efectos secundarios: caché, logs, sincronizaciones externas.
 *
 * @module EmployeesModule
 *
 * @type Observer
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        Cache::forget('employees_list');
    }

    public function updated(Employee $employee): void
    {
        Cache::forget("employee:{$employee->id}");
        Cache::forget('employees_list');
    }

    public function deleted(Employee $employee): void
    {
        Cache::forget("employee:{$employee->id}");
        Cache::forget('employees_list');
    }

    public function restored(Employee $employee): void
    {
        Cache::forget('employees_list');
    }

    public function forceDeleted(Employee $employee): void
    {
        Cache::forget("employee:{$employee->id}");
        Cache::forget('employees_list');
    }
}
