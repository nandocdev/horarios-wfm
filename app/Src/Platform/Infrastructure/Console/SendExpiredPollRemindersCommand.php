<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Console;

use App\Src\Platform\Application\Handlers\SendExpiredPollRemindersHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SendExpiredPollRemindersCommand extends Command {
    protected $signature = 'platform:communications:poll-reminders
                            {--dry-run : Solo mostrar cuántos recordatorios se enviarían sin ejecutar}';

    protected $description = 'Envía recordatorios a usuarios que no han votado en encuestas que están por expirar';

    public function handle(SendExpiredPollRemindersHandler $handler): int {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY-RUN] No se realizarán cambios. Simulando envío de recordatorios...');

            return self::SUCCESS;
        }

        $this->info('Enviando recordatorios de encuestas expiradas...');

        $result = $handler->execute();

        $this->info("Encuestas procesadas: {$result['polls']}");
        $this->info("Notificaciones enviadas: {$result['notifications']}");

        Log::channel('audit')->info('SendExpiredPollReminders completed', $result);

        return self::SUCCESS;
    }
}
