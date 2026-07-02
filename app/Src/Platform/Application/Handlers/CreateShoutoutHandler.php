<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\ShoutoutDTO;
use App\Src\Platform\Domain\Entities\Shoutout;
use App\Src\Platform\Domain\Repositories\CategoryRepositoryInterface;
use App\Src\Platform\Domain\Repositories\ShoutoutRepositoryInterface;
use App\Src\Platform\Domain\Repositories\TagRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class CreateShoutoutHandler
{
    public function __construct(
        private ShoutoutRepositoryInterface $shoutoutRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private TagRepositoryInterface $tagRepository,
    ) {}

    public function execute(ShoutoutDTO $dto): Shoutout
    {
        return DB::transaction(function () use ($dto) {
            $status = $dto->workflowAction === 'submit_review' ? 'pending_review' : 'draft';

            $shoutout = Shoutout::create(
                employeeId: $dto->employeeId,
                message: $dto->message,
                isActive: $dto->isActive,
                status: $status,
                scheduledAt: $dto->scheduledAt,
                archiveAt: $dto->archiveAt,
            );

            $shoutout = $this->shoutoutRepository->save($shoutout);

            $this->categoryRepository->syncForContent($shoutout->id(), Shoutout::class, $dto->categoryIds);
            $this->tagRepository->syncForContent($shoutout->id(), Shoutout::class, $dto->tagIds);

            return $shoutout;
        });
    }
}
