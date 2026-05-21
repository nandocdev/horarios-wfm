<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\OperationsModule\Actions\CalculateAdvancedProductivityAction;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateAgentDailyMetricsCommand extends Command
{
    protected $signature = 'wfm:aggregate-metrics {date? : La fecha a procesar (YYYY-MM-DD)} {--all : Procesar todos los empleados}';

    protected $description = 'Agrega y persiste las métricas operativas avanzadas (WU, PWI, Capacidad) para los agentes.';

    public function handle(CalculateAdvancedProductivityAction $calculateAction): int
    {
        $dateStr = $this->argument('date') ?? now()->subDay()->toDateString();
        $date = Carbon::parse($dateStr);

        $this->info("Iniciando agregación de métricas para la fecha: {$date->toDateString()}");

        $employees = Employee::query()
            ->whereIn('position_id', [1, 2, 5]) // Solo posiciones operativas
            ->get();

        $this->withProgressBar($employees, function ($employee) use ($date, $calculateAction) {
            try {
                $metrics = $calculateAction->execute($employee, $date);
                
                $attributes = $metrics->getAttributes();
                
                // Aseguramos que la fecha sea solo Y-m-d para la búsqueda
                $searchDate = $date->toDateString();

                \App\Modules\OperationsModule\Models\AgentDailyMetric::updateOrCreate(
                    ['employee_id' => $employee->id, 'metric_date' => $searchDate],
                    $attributes
                );
            } catch (\Exception $e) {
                $this->error("\nError procesando empleado {$employee->id}: " . $e->getMessage());
            }
        });

        $this->newLine();
        $this->info("Agregación completada para {$employees->count()} empleados.");

        return self::SUCCESS;
    }
}
