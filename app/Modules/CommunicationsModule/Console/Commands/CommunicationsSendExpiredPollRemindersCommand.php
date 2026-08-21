<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\CommunicationsModule\Actions\SendExpiredPollRemindersAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('communications:send-expired-poll-reminders')]
#[Description('Envía recordatorios automáticos de encuestas expiradas')]
class CommunicationsSendExpiredPollRemindersCommand extends Command
{
    public function handle(SendExpiredPollRemindersAction $action): int
    {
        $result = $action->execute();

        $this->info("Encuestas procesadas: {$result['polls']}");
        $this->info("Notificaciones enviadas: {$result['notifications']}");

        return self::SUCCESS;
    }
}
