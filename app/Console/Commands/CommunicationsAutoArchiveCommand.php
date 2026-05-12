<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\CommunicationsModule\Actions\AutoArchiveContentAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('communications:auto-archive')]
#[Description('Archiva automáticamente contenido vencido por fecha en comunicaciones')]
class CommunicationsAutoArchiveCommand extends Command
{
    public function handle(AutoArchiveContentAction $action): int
    {
        $result = $action->execute();

        $this->info("Archivados - noticias: {$result['news']}, encuestas: {$result['polls']}, shoutouts: {$result['shoutouts']}");

        return self::SUCCESS;
    }
}
