<?php

declare(strict_types=1);

namespace App\Src\Analytics\Application\Handlers;

use App\Modules\PersonnelModule\Models\Employee;
use DateTimeImmutable;

final class BatchAggregateHandler
{
    public function __construct(
        private CalculateDailyMetricsHandler $metricsHandler,
    ) {}

    public function handle(DateTimeImmutable $date, ?array $employeeIds = null): array
    {
        $employees = $employeeIds
            ? Employee::whereIn('id', $employeeIds)->active()->get()
            : Employee::active()->get();

        $results = [];

        foreach ($employees as $employee) {
            try {
                $metric = $this->metricsHandler->handle($employee->id, $date);
                $results[] = [
                    'employee_id' => $employee->id,
                    'status' => 'ok',
                    'pwi' => $metric->pwiPct(),
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'employee_id' => $employee->id,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
