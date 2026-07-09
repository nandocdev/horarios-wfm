<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Modules\ConnectModule\Actions\SyncCuicDataAction;
use App\Modules\OperationsModule\Actions\ReconcileEmployeeAttendanceAction;
use App\Shared\Contracts\Employees\EmployeeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class CuicSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cuic:sync 
                            {--minutes=60 : Cantidad de minutos hacia atrás para sincronizar}
                            {--from= : Fecha de inicio (Y-m-d H:i:s)}
                            {--to= : Fecha de fin (Y-m-d H:i:s)}
                            {--loop : Ejecutar en bucle infinito}
                            {--interval=300 : Intervalo en segundos entre cada sincronización (default 5 min)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronización incremental de datos desde Cisco CUIC (Patrón ETL)';

    /**
     * Execute the console command.
     */
    public function handle(SyncCuicDataAction $action): int
    {
        if ($this->option('loop')) {
            $this->info('=== Iniciando Sincronización CUIC ETL Continua (Ctrl+C para detener) ===');
            $interval = (int) $this->option('interval');

            while (true) {
                $this->performSync($action);
                $this->line("Esperando {$interval}s para el próximo ciclo...");
                sleep($interval);
            }
        }

        $this->info('=== Iniciando Sincronización CUIC ETL Única ===');

        return $this->performSync($action);
    }

    private function performSync(SyncCuicDataAction $action): int
    {
        if ($this->option('from') && $this->option('to')) {
            $start = Carbon::parse((string) $this->option('from'));
            $end = Carbon::parse((string) $this->option('to'));
        } else {
            $minutes = (int) $this->option('minutes');
            $end = now();
            $start = now()->subMinutes($minutes);
        }

        $this->line('['.now()->format('H:i:s')."] Rango: <comment>{$start->toDateTimeString()}</comment> a <comment>{$end->toDateTimeString()}</comment>");

        try {
            $stats = $action->execute($start, $end);

            $this->table(
                ['Tipo de Datos', 'Registros'],
                array_map(fn ($k, $v) => [ucfirst($k), $v], array_keys($stats), array_values($stats))
            );

            // Trigger reconciliation for today and yesterday if transitions were synced
            if (($stats['transitions'] ?? 0) > 0) {
                $this->line('Iniciando reconciliación de asistencia post-sync...');
                $reconcileAction = app(ReconcileEmployeeAttendanceAction::class);
                $employeeRepo = app(EmployeeRepositoryInterface::class);
                $employees = $employeeRepo->findActive();

                foreach ([now(), now()->subDay()] as $date) {
                    foreach ($employees as $employee) {
                        $reconcileAction->execute($employee, $date);
                    }
                }
                $this->info('Reconciliación completada.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error durante la sincronización: '.$e->getMessage());

            Log::error('[CUIC-SYNC] Fallo en comando', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
