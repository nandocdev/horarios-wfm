<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\CommunicationsModule\Actions\PublishScheduledContentAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('communications:publish-scheduled')]
#[Description('Publica contenido programado del módulo de comunicaciones')]
class CommunicationsPublishScheduledCommand extends Command
{
    public function handle(PublishScheduledContentAction $action): int
    {
        $result = $action->execute();

        $this->info("Noticias publicadas: {$result['news']}");

        return self::SUCCESS;
    }
}
