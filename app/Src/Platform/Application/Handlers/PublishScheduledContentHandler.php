<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Repositories\NewsRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class PublishScheduledContentHandler
{
    public function __construct(
        private NewsRepositoryInterface $newsRepository,
    ) {}

    public function execute(): array
    {
        return DB::transaction(function () {
            $now = now();

            $published = $this->newsRepository->publishScheduled($now);

            return [
                'news' => $published,
            ];
        });
    }
}
