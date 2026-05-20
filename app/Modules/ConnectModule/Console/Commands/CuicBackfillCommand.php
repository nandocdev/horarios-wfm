<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Modules\ConnectModule\Actions\SyncCuicDataAction;
use App\Modules\ConnectModule\Emails\CuicBackfillReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class CuicBackfillCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cuic:backfill 
                            {--months=1 : Meses hacia atrás a procesar}
                            {--days= : Días hacia atrás (prioridad sobre months)}
                            {--chunk=30 : Tamaño del intervalo en minutos}
                            {--delay=1 : Segundos de espera entre peticiones}
                            {--unattended : Ignorar errores y continuar sin intervención}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronización histórica masiva (Backfill) desde Cisco CUIC';

    /**
     * Execute the console command.
     */
    public function handle(SyncCuicDataAction $action): int
    {
        $months = (int) $this->option('months');
        $days = $this->option('days');
        $chunkSize = (int) $this->option('chunk');
        $delay = (int) $this->option('delay');
        $unattended = $this->option('unattended');

        $endDate = now();

        if ($days !== null) {
            $startDate = now()->subDays((int) $days)->startOfDay();
        } else {
            if ($months < 1 || $months > 6) {
                $this->error('El rango de meses debe estar entre 1 y 6.');

                return self::FAILURE;
            }
            $startDate = now()->subMonths($months)->startOfDay();
        }

        $this->info('=== Iniciando Backfill Histórico CUIC ===');
        $this->line("Periodo: <comment>{$startDate->toDateTimeString()}</comment> al <comment>{$endDate->toDateTimeString()}</comment>");
        $this->line("Intervalos: <comment>{$chunkSize} minutos</comment> | Descanso: <comment>{$delay}s</comment>");
        if ($unattended) {
            $this->warn('MODO DESATENDIDO: Los errores serán registrados pero el proceso no se detendrá.');
        }

        $totalMinutes = $startDate->diffInMinutes($endDate);
        $totalSteps = (int) ceil($totalMinutes / $chunkSize);
        $this->line("Total de pasos: <comment>{$totalSteps}</comment>");

        $bar = $this->output->createProgressBar($totalSteps);
        $bar->start();

        // Evitar timeouts
        set_time_limit(0);

        // Variables de control y estadísticas
        $currentIntervalStart = $startDate->copy();
        $lastProcessedDay = $currentIntervalStart->toDateString();
        $dailyStats = $this->resetDailyStats();

        while ($currentIntervalStart->lessThan($endDate)) {
            // Calculamos donde termina este bloque
            $currentIntervalEnd = $currentIntervalStart->copy()->addMinutes($chunkSize);

            // Si el bloque se pasa de la fecha límite final, lo topamos
            if ($currentIntervalEnd->greaterThan($endDate)) {
                $currentIntervalEnd = $endDate->copy();
            }

            // Detectar cambio de día para enviar reporte
            if ($currentIntervalStart->toDateString() !== $lastProcessedDay) {
                $this->sendDailyReport($lastProcessedDay, $dailyStats);
                $lastProcessedDay = $currentIntervalStart->toDateString();
                $dailyStats = $this->resetDailyStats();
            }

            // --- ESTADO EN CONSOLA ---
            if ($currentIntervalStart->format('H:i') === '00:00' || $currentIntervalStart->equalTo($startDate)) {
                $this->newLine();
                $this->info("--- Procesando Día: {$currentIntervalStart->toDateString()} ---");
            }

            // @description: Ejecutar sincronización
            try {
                $batchStats = $action->execute($currentIntervalStart, $currentIntervalEnd);

                foreach ($batchStats as $type => $count) {
                    $dailyStats['by_type'][$type] = ($dailyStats['by_type'][$type] ?? 0) + $count;
                    $dailyStats['total_records'] += $count;
                }

                $bar->advance();
            } catch (\Throwable $e) {
                $this->newLine();
                $errorMsg = "[{$currentIntervalStart->format('H:i')}] ".$e->getMessage();
                $this->error('Error: '.$errorMsg);

                $dailyStats['errors'][] = $errorMsg;

                // En modo desatendido continuamos, de lo contrario preguntamos
                if (! $unattended && ! $this->option('no-interaction')) {
                    if (! $this->confirm('¿Deseas continuar con el siguiente bloque?', true)) {
                        $bar->finish();

                        return self::FAILURE;
                    }
                }
            }

            // Descanso entre peticiones
            if ($delay > 0) {
                sleep($delay);
            }

            // AVANZAR
            $currentIntervalStart = $currentIntervalEnd->copy();
        }

        // Enviar reporte del último día procesado
        $this->sendDailyReport($lastProcessedDay, $dailyStats);

        $bar->finish();
        $this->newLine();
        $this->info('Backfill Histórico completado exitosamente.');

        return self::SUCCESS;
    }

    /**
     * Reinicia el acumulador de estadísticas diarias.
     */
    private function resetDailyStats(): array
    {
        return [
            'total_records' => 0,
            'by_type' => ['transitions' => 0, 'performance' => 0, 'calls' => 0, 'chats' => 0],
            'errors' => [],
        ];
    }

    /**
     * Envía el reporte por correo electrónico.
     */
    private function sendDailyReport(string $date, array $stats): void
    {
        // Solo enviar si hubo actividad o errores
        if ($stats['total_records'] === 0 && empty($stats['errors'])) {
            return;
        }

        try {
            Mail::to('ferncastillo@css.gob.pa')
                ->send(new CuicBackfillReport($date, $stats));

            $this->line(' <info>✔</info> Reporte diario enviado a ferncastillo@css.gob.pa');
        } catch (\Throwable $e) {
            $this->error('No se pudo enviar el reporte diario: '.$e->getMessage());
            Log::error('[CUIC-BACKFILL] Fallo envío reporte email', ['error' => $e->getMessage()]);
        }
    }
}
