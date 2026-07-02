<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Infrastructure\Console\Commands;

use App\Modules\AuditModule\Application\PruneAuditLogs\Command as PruneCommand;
use App\Modules\AuditModule\Application\PruneAuditLogs\Handler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class AuditPruneCommand extends Command
{
    protected $signature = 'audit:prune
                            {--days=90 : Días de retención de logs (anteriores a esto se eliminan)}
                            {--chunk=500 : Tamaño de lote para eliminación}
                            {--dry-run : Solo mostrar cuántos registros se eliminarían}';

    protected $description = 'Elimina registros de auditoría antiguos según la política de retención';

    public function handle(Handler $handler): int
    {
        $command = new PruneCommand(
            days: (int) $this->option('days'),
            chunkSize: (int) $this->option('chunk'),
            dryRun: (bool) $this->option('dry-run'),
        );

        $result = $handler($command);

        if ($result->affected === 0) {
            $this->info("No hay registros de auditoría anteriores a {$result->cutoff->format('Y-m-d')}.");

            return self::SUCCESS;
        }

        if ($result->dryRun) {
            $this->warn("[DRY-RUN] Se eliminarían {$result->affected} registros anteriores a {$result->cutoff->format('Y-m-d')}.");

            return self::SUCCESS;
        }

        $this->info("Eliminados {$result->affected} registros de auditoría anteriores a {$result->cutoff->format('Y-m-d')}.");

        Log::channel('audit')->info('AuditLog prune completed', [
            'deleted' => $result->affected,
            'cutoff' => $result->cutoff->format('Y-m-d'),
            'days_retained' => $this->option('days'),
        ]);

        return self::SUCCESS;
    }
}
