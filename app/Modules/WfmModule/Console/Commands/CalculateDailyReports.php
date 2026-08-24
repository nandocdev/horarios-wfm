<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Console\Commands;

use App\Modules\WfmModule\Actions\CalculateDailyOperatorReportAction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateDailyReports extends Command
{
    protected $signature = 'wfm:calculate-daily-reports
        {--date= : Fecha específica (Y-m-d). Por defecto: ayer}
        {--all : Calcular para todos los días desde --date hasta hoy}';

    protected $description = 'Calcula y almacena reportes diarios de operadores';

    public function handle(CalculateDailyOperatorReportAction $action): int
    {
        $date = $this->option('date') ?: now()->subDay()->toDateString();
        $all = $this->option('all');

        if ($all) {
            $start = Carbon::parse($date);
            $end = now()->subDay();
            $days = [];

            while ($start->lte($end)) {
                $days[] = $start->toDateString();
                $start = $start->addDay();
            }

            $this->info("Calculando reportes para {$date} hasta hoy...");

            foreach ($days as $day) {
                $result = $action->executeAll($day);
                $this->line("  {$day}: {$result['success']} ok, {$result['error']} errores");
            }
        } else {
            $this->info("Calculando reportes para {$date}...");
            $result = $action->executeAll($date);
            $this->info("{$result['success']} reportes calculados, {$result['error']} errores.");
        }

        return self::SUCCESS;
    }
}
