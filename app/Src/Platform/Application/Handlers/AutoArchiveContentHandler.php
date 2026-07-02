<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Repositories\NewsRepositoryInterface;
use App\Src\Platform\Domain\Repositories\PollRepositoryInterface;
use App\Src\Platform\Domain\Repositories\ShoutoutRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class AutoArchiveContentHandler
{
    public function __construct(
        private NewsRepositoryInterface $newsRepository,
        private PollRepositoryInterface $pollRepository,
        private ShoutoutRepositoryInterface $shoutoutRepository,
    ) {}

    public function execute(): array
    {
        return DB::transaction(function () {
            $now = now();

            $newsArchived = $this->newsRepository->archiveExpired($now);
            $pollsArchived = $this->pollRepository->archiveExpired($now);
            $shoutoutsArchived = $this->shoutoutRepository->archiveExpired($now);

            return [
                'news' => $newsArchived,
                'polls' => $pollsArchived,
                'shoutouts' => $shoutoutsArchived,
            ];
        });
    }
}
