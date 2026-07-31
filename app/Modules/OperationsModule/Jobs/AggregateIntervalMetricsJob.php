<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Jobs;

use App\Modules\OperationsModule\Actions\GenerateAgentIntervalMetricsAction;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class AggregateIntervalMetricsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct()
    {
        $this->onQueue('wfm-heavy');
    }

    public function handle(GenerateAgentIntervalMetricsAction $action): void
    {
        $now = CarbonImmutable::now()->startOfMinute();
        $intervalMinutes = 15;
        $intervalModulus = $now->minute % $intervalMinutes;
        $intervalStart = $now->subMinutes($intervalModulus + $intervalMinutes)->second(0);
        $intervalEnd = $intervalStart->addMinutes($intervalMinutes);

        $employees = Employee::where('is_active', true)->pluck('id');

        $processed = 0;
        foreach ($employees as $employeeId) {
            try {
                $result = $action->execute($employeeId, $intervalStart, $intervalEnd);
                if ($result !== null) {
                    $processed++;
                }
            } catch (\Throwable $e) {
                Log::warning('Error generando métricas de intervalo', [
                    'employee_id' => $employeeId,
                    'interval' => $intervalStart->toDateTimeString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Métricas de intervalo generadas', [
            'interval_start' => $intervalStart->toDateTimeString(),
            'interval_end' => $intervalEnd->toDateTimeString(),
            'employees_processed' => $processed,
            'total_employees' => $employees->count(),
        ]);
    }
}
