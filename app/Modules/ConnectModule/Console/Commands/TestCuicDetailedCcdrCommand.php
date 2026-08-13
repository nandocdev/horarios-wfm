<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Modules\ConnectModule\Enums\ContactDisposition;
use App\Modules\ConnectModule\Enums\ContactType;
use App\Modules\ConnectModule\Services\CuicReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Comando de prueba e inspección del reporte CCDR detallado de Cisco CUIC.
 *
 * Uso:
 *   php artisan cuic:test-detailed-ccdr
 *   php artisan cuic:test-detailed-ccdr --date=2026-08-13 --start=08:00 --end=09:00
 */
class TestCuicDetailedCcdrCommand extends Command
{
    protected $signature = 'cuic:test-detailed-ccdr
                            {--report=detailed_call_by_call_ccdr : Clave del reporte en config}
                            {--date=   : Fecha en formato Y-m-d (default: hoy)}
                            {--start=  : Hora de inicio HH:MM (default: 06:00)}
                            {--end=    : Hora de fin HH:MM   (default: 07:00)}';

    protected $description = '[CUIC] Prueba el reporte detailed_call_by_call_ccdr con rango horario';

    public function handle(CuicReportService $service): int
    {
        $dateStr = $this->option('date') ?: Carbon::today()->toDateString();
        $startStr = $this->option('start') ?: '06:00';
        $endStr = $this->option('end') ?: '07:00';

        [$startH, $startM] = array_map('intval', explode(':', $startStr));
        [$endH,   $endM] = array_map('intval', explode(':', $endStr));

        $startDateTime = Carbon::parse($dateStr)->setTime($startH, $startM, 0);
        $endDateTime = Carbon::parse($dateStr)->setTime($endH, $endM, 59);

        $reportKey = (string) $this->option('report');

        $this->info('');
        $this->line("  <fg=cyan;options=bold>CUIC › {$reportKey} — Inspección CCDR</>");
        $this->line('  ─────────────────────────────────────────────');
        $this->line("  Fecha  : <comment>{$dateStr}</comment>");
        $this->line("  Rango  : <comment>{$startDateTime->format('H:i:s')}</comment> → <comment>{$endDateTime->format('H:i:s')}</comment>");
        $this->line('  ─────────────────────────────────────────────');
        $this->info('');

        $this->line('  ⏳ Conectando con CUIC...');

        try {
            $rows = $service->executeReportWithFilter($reportKey, $startDateTime, $endDateTime);
        } catch (\Throwable $e) {
            $this->error('  ✗ Error: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($rows->isEmpty()) {
            $this->warn('  ⚠ Sin datos para el rango indicado.');

            return self::SUCCESS;
        }

        $this->info("  ✓ {$rows->count()} filas recibidas de CUIC");
        $this->info('');

        $sample = $rows->take(15)->map(function (array $row): array {
            $cCallId = $row['session_id'] ?? $row['session_id_seq'] ?? '—';
            $seq = $row['sequence_num'] ?? $row['sequence_number'] ?? '—';
            $contactTypeVal = isset($row['contact_type']) ? (int) $row['contact_type'] : null;
            $typeLabel = $contactTypeVal ? (ContactType::tryFrom($contactTypeVal)?->label() ?? (string) $contactTypeVal) : '—';
            $dispVal = (int) ($row['contact_disposition'] ?? 0);
            $dispLabel = ContactDisposition::statusFor($dispVal);

            return [
                'Session-Seq' => "{$cCallId}-{$seq}",
                'Tipo' => $typeLabel,
                'Disposición' => "{$dispVal} ({$dispLabel})",
                'Origen' => $row['originator_dn'] ?? $row['originator_id'] ?? '—',
                'Destino' => $row['destination_dn'] ?? $row['destination_id'] ?? '—',
                'Talk (s)' => $row['talk_time'] ?? 0,
                'Hold (s)' => $row['hold_time'] ?? 0,
                'Queue (s)' => $row['queue_time'] ?? 0,
            ];
        })->toArray();

        $this->table(
            ['Session-Seq', 'Tipo', 'Disposición', 'Origen', 'Destino', 'Talk (s)', 'Hold (s)', 'Queue (s)'],
            $sample
        );

        if ($rows->count() > 15) {
            $this->line('  <fg=gray>... y '.($rows->count() - 15).' filas más.</>');
        }

        return self::SUCCESS;
    }
}
