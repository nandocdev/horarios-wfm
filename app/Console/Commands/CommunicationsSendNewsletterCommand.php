<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\CommunicationsModule\Actions\SendAutomaticNewsletterAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('communications:send-newsletter')]
#[Description('Envía newsletter automático diario de comunicaciones')]
class CommunicationsSendNewsletterCommand extends Command
{
    public function handle(SendAutomaticNewsletterAction $action): int
    {
        $result = $action->execute();

        $this->info("Noticias incluidas: {$result['news']}");
        $this->info("Newsletters enviadas: {$result['notifications']}");

        return self::SUCCESS;
    }
}
