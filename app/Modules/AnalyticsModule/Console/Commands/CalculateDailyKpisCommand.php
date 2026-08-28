<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Console\Commands;

use App\Modules\AnalyticsModule\Actions\CalculateDailyKpisAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CalculateDailyKpisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:calculate-daily-kpis
                            {--date= : Fecha específica (YYYY-MM-DD)}
                            {--from= : Fecha inicial para rango (YYYY-MM-DD)}
                            {--to= : Fecha final para rango (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula y consolida KPIs diarios (DailyKpi) para agentes, equipos y nivel global';

    public function handle(CalculateDailyKpisAction $action): int
    {
        $dateOption = $this->option('date');
        $fromOption = $this->option('from');
        $toOption = $this->option('to');

        if ($fromOption && $toOption) {
            $from = CarbonImmutable::parse((string) $fromOption);
            $to = CarbonImmutable::parse((string) $toOption);

            if ($from->isAfter($to)) {
                $this->error('La fecha inicial (--from) no puede ser posterior a la fecha final (--to).');

                return self::FAILURE;
            }

            $current = $from;
            $this->info("Consolidando KPIs diarios desde {$from->toDateString()} hasta {$to->toDateString()}...");

            while ($current->isBefore($to) || $current->isSameDay($to)) {
                $this->processDate($action, $current);
                $current = $current->addDay();
            }
        } else {
            $date = $dateOption
                ? CarbonImmutable::parse((string) $dateOption)
                : CarbonImmutable::yesterday();

            $this->processDate($action, $date);
        }

        $this->info('Consolidación de KPIs diarios completada.');

        return self::SUCCESS;
    }

    private function processDate(CalculateDailyKpisAction $action, CarbonImmutable $date): void
    {
        $dateStr = $date->toDateString();
        $this->line("Procesando fecha: <comment>{$dateStr}</comment>");

        $result = $action->execute($date);

        $this->line("  ✓ Empleados: {$result['employees']}, Equipos: {$result['teams']}, Global: {$result['global']}");
    }
}
