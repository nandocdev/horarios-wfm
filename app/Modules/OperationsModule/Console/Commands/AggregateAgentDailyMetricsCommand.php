<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Console\Commands;

use App\Modules\OperationsModule\Actions\CalculateAdvancedProductivityAction;
use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateAgentDailyMetricsCommand extends Command
{
    protected $signature = 'wfm:aggregate-metrics 
                            {date? : La fecha final o única a procesar (YYYY-MM-DD). Por defecto: ayer} 
                            {--from= : Si se especifica, procesa desde esta fecha hasta la fecha final} 
                            {--all : Procesar todos los empleados}';

    protected $description = 'Agrega y persiste las métricas operativas avanzadas (WU, PWI, Capacidad) para los agentes en una fecha o rango.';

    public function handle(CalculateAdvancedProductivityAction $calculateAction): int
    {
        $endDateStr = $this->argument('date') ?? now()->subDay()->toDateString();
        $endDate = Carbon::parse($endDateStr);

        $startDate = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : $endDate->copy();

        if ($startDate->gt($endDate)) {
            $this->error('La fecha de inicio (--from) no puede ser posterior a la fecha final.');

            return self::FAILURE;
        }

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $this->processDate($currentDate, $calculateAction);
            $currentDate->addDay();
        }

        return self::SUCCESS;
    }

    protected function processDate(Carbon $date, CalculateAdvancedProductivityAction $calculateAction): void
    {
        $dateStr = $date->toDateString();
        $this->info("Iniciando agregación de métricas para la fecha: {$dateStr}");

        $employees = Employee::query()
            ->whereIn('position_id', [1, 2, 5, 11, 13]) // Solo posiciones operativas
            ->get();

        $this->withProgressBar($employees, function ($employee) use ($date, $calculateAction) {
            try {
                $metrics = $calculateAction->execute($employee, $date);
                $attributes = $metrics->getAttributes();

                AgentDailyMetric::updateOrCreate(
                    ['employee_id' => $employee->id, 'metric_date' => $date->toDateString()],
                    $attributes
                );
            } catch (\Exception $e) {
                // Loguear error pero continuar con el siguiente empleado
                \Log::error("Error procesando métricas: Emp {$employee->id} en {$date->toDateString()}: ".$e->getMessage());
            }
        });

        $this->newLine();
        $this->info("Agregación completada para {$employees->count()} empleados en la fecha {$dateStr}.");
    }
}
