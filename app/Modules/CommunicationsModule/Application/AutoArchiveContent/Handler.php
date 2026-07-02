<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\AutoArchiveContent;

use App\Modules\CommunicationsModule\Domain\Services\ContentScheduler;

final readonly class Handler
{
    public function __construct(
        private ContentScheduler $scheduler,
    ) {}

    public function __invoke(): array
    {
        return $this->scheduler->archiveExpired();
    }
}
