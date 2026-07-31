<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Console\Commands;

use App\Modules\OperationsModule\Actions\CalculateDailyMetricsAction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateDailyMetricsCommand extends Command
{
    protected $signature = 'operations:calculate-daily-metrics {--date= : Fecha en formato YYYY-MM-DD. Si no se provee, toma el día de ayer}';

    protected $description = 'Proceso ETL que pre-calcula métricas diarias para Colas y Agentes desde la telemetría';

    public function handle(CalculateDailyMetricsAction $action): int
    {
        $dateInput = $this->option('date');

        $date = $dateInput ? Carbon::parse($dateInput)->toDateString() : Carbon::yesterday()->toDateString();

        $this->info("Iniciando cálculo de métricas diarias para la fecha: {$date}");

        try {
            $action->execute($date);
            $this->info('Cálculo de métricas finalizado con éxito.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Ocurrió un error al calcular las métricas: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
