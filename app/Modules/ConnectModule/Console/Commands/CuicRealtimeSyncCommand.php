<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Console\Commands;

use App\Modules\ConnectModule\Actions\SyncCsqRealtimeStatsAction;
use Illuminate\Console\Command;

class CuicRealtimeSyncCommand extends Command
{
    protected $signature = 'cuic:sync-realtime {--loop : Ejecutar en bucle infinito} {--interval=10 : Intervalo en segundos entre cada sincronización}';

    protected $description = 'Sincroniza métricas de colas (CSQ) en tiempo real desde Cisco CUIC';

    public function handle(SyncCsqRealtimeStatsAction $action): int
    {
        if ($this->option('loop')) {
            $this->info('Iniciando sincronización continua de métricas CSQ (Ctrl+C para detener)...');
            
            while (true) {
                $this->performSync($action);
                sleep((int) $this->option('interval'));
            }
        }

        $this->info('Iniciando sincronización única de métricas CSQ...');
        $this->performSync($action);

        return self::SUCCESS;
    }

    private function performSync(SyncCsqRealtimeStatsAction $action): void
    {
        try {
            $count = $action->execute();
            if ($count > 0) {
                $this->success("[" . now()->format('H:i:s') . "] Sincronizadas {$count} colas exitosamente.");
            }
        } catch (\Exception $e) {
            $this->error("[" . now()->format('H:i:s') . "] Error: " . $e->getMessage());
        }
    }

    private function success(string $message): void
    {
        $this->output->writeln("<info>✔</info> {$message}");
    }
}
