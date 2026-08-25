<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Console\Commands;

use App\Modules\AuditModule\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AuditPruneCommand extends Command
{
    protected $signature = 'audit:prune
                            {--days=90 : Días de retención de logs (anteriores a esto se eliminan)}
                            {--chunk=500 : Tamaño de lote para eliminación}
                            {--dry-run : Solo mostrar cuántos registros se eliminarían}';

    protected $description = 'Elimina registros de auditoría antiguos según la política de retención';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subDays($days);
        $query = AuditLog::where('created_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info("No hay registros de auditoría anteriores a {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("[DRY-RUN] Se eliminarían {$count} registros anteriores a {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $deleted = 0;

        // El trigger de inmutabilidad permite DELETE solo con el flag de
        // sesión app.audit_maintenance activo (set_config con is_local=true
        // lo limita a la transacción actual).
        DB::beginTransaction();
        DB::select("SELECT set_config('app.audit_maintenance', 'on', true)");

        try {
            $query->chunkById($chunk, function ($logs) use ($bar, &$deleted) {
                foreach ($logs as $log) {
                    $log->delete();
                    $deleted++;
                    $bar->advance();
                }
            });

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        $bar->finish();
        $this->newLine();
        $this->info("Eliminados {$deleted} registros de auditoría anteriores a {$cutoff->toDateString()}.");

        Log::channel('audit')->info('AuditLog prune completed', [
            'deleted' => $deleted,
            'cutoff' => $cutoff->toDateString(),
            'days_retained' => $days,
        ]);

        return self::SUCCESS;
    }
}
