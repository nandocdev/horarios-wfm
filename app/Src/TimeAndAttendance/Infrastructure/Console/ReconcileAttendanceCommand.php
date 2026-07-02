<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Infrastructure\Console;

use App\Modules\PersonnelModule\Models\Employee;
use App\Src\TimeAndAttendance\Domain\Services\EndOfDayReconciliationService;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class ReconcileAttendanceCommand extends Command
{
    protected $signature = 'attendance:reconcile {date? : Fecha (Y-m-d)}';
    protected $description = 'Ejecuta la conciliación de asistencia al final de la jornada';

    public function handle(EndOfDayReconciliationService $service): int
    {
        $date = new DateTimeImmutable($this->argument('date') ?? date('Y-m-d'));

        $this->info("Conciliando asistencia para fecha: {$date->format('Y-m-d')}");

        $employees = Employee::active()->get();
        $totalIncidents = 0;
        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        foreach ($employees as $employee) {
            try {
                $incidents = $service->reconcile($employee->id, $date);
                $totalIncidents += count($incidents);
            } catch (\Throwable $e) {
                Log::warning("Error reconciliando empleado {$employee->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Conciliación completada. {$totalIncidents} incidencias generadas.");

        return self::SUCCESS;
    }
}
