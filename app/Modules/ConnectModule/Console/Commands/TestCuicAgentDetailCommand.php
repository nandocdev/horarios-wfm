<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Modules\ConnectModule\Actions\FetchAgentDetailAction;
use App\Modules\ConnectModule\Services\CuicReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Comando de prueba de integración con CUIC.
 *
 * Ejecuta el reporte `agent_detail` para ayer de 06:00 a 07:00
 * y muestra los resultados en consola.
 *
 * Uso:
 *   php artisan cuic:test-agent-detail
 *   php artisan cuic:test-agent-detail --date=2026-04-28 --start=06:00 --end=07:00
 *   php artisan cuic:test-agent-detail --date=2026-04-28 --agent="Amalia Renteria"
 */
class TestCuicAgentDetailCommand extends Command
{
    protected $signature = 'cuic:test-agent-detail
                            {--report=agent_detail : Clave del reporte en config}
                            {--date=   : Fecha en formato Y-m-d (default: ayer)}
                            {--start=  : Hora de inicio HH:MM (default: 06:00)}
                            {--end=    : Hora de fin HH:MM   (default: 07:00)}
                            {--agent=* : Nombre(s) de agente a filtrar (default: todos)}';

    protected $description = '[CUIC] Prueba el reporte agent_detail con rango horario';

    public function __construct(
        private readonly FetchAgentDetailAction $action
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // --- Resolución de parámetros ---
        $dateStr = $this->option('date') ?: Carbon::yesterday()->toDateString();
        $startStr = $this->option('start') ?: '06:00';
        $endStr = $this->option('end') ?: '07:00';

        [$startH, $startM] = array_map('intval', explode(':', $startStr));
        [$endH,   $endM] = array_map('intval', explode(':', $endStr));

        $startDateTime = Carbon::parse($dateStr)->setTime($startH, $startM, 0);
        $endDateTime = Carbon::parse($dateStr)->setTime($endH, $endM, 59);

        /** @var array<int, string> $agentNames */
        $agentNames = (array) $this->option('agent');

        $reportKey = (string) $this->option('report');

        // --- Presentación ---
        $this->info('');
        $this->line("  <fg=cyan;options=bold>CUIC › {$reportKey} — Prueba de integración</>");
        $this->line('  ─────────────────────────────────────────────');
        $this->line("  Fecha  : <comment>{$dateStr}</comment>");
        $this->line("  Rango  : <comment>{$startDateTime->format('H:i:s')}</comment> → <comment>{$endDateTime->format('H:i:s')}</comment>");
        $this->line('  Agentes: <comment>'.(empty($agentNames) ? 'TODOS' : implode(', ', $agentNames)).'</comment>');
        $this->line('  ─────────────────────────────────────────────');
        $this->info('');

        $this->line('  ⏳ Conectando con CUIC...');

        try {
            /** @var CuicReportService $service */
            $service = app(CuicReportService::class);
            $rows = $service->executeReportWithFilter($reportKey, $startDateTime, $endDateTime, $agentNames);
        } catch (\Throwable $e) {
            $this->error('  ✗ Error: '.$e->getMessage());

            return self::FAILURE;
        }

        // --- Resultados ---
        if ($rows->isEmpty()) {
            $this->warn('  ⚠ Sin datos para el rango indicado.');

            return self::SUCCESS;
        }

        $this->info("  ✓ {$rows->count()} filas recibidas de CUIC");
        $this->info('');

        // Mostrar las primeras 20 filas en tabla
        $sample = $rows->take(20)->map(function (array $row): array {
            // Normalizar transition_time (epoch ms → H:i:s) si el campo existe
            $time = isset($row['transition_time'])
                ? Carbon::createFromTimestampMs((int) $row['transition_time'], 'UTC')->tz(config('app.timezone'))->format('H:i:s')
                : 'N/A';

            return [
                'Agente' => $row['agent_name'] ?? $row['agent_login_id'] ?? '—',
                'Hora' => $time,
                'Estado' => $row['agent_state'] ?? '—',
                'Motivo' => $row['reason_code'] ?? '—',
                'Duración (seg)' => $row['duration'] ?? '—',
            ];
        })->toArray();

        $this->table(
            ['Agente', 'Hora', 'Estado', 'Motivo', 'Duración (seg)'],
            $sample
        );

        if ($rows->count() > 20) {
            $this->line('  <fg=gray>... y '.($rows->count() - 20).' filas más (usa --agent para filtrar)</>');
        }

        // Dump completo opcional
        if ($this->confirm('  ¿Deseas ver el JSON completo de la primera fila?', false)) {
            $this->line(json_encode($rows->first(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $this->info('');

        return self::SUCCESS;
    }
}
