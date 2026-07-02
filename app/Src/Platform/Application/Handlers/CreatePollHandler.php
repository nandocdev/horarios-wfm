<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\PollDTO;
use App\Src\Platform\Domain\Entities\Poll;
use App\Src\Platform\Domain\Repositories\CategoryRepositoryInterface;
use App\Src\Platform\Domain\Repositories\PollRepositoryInterface;
use App\Src\Platform\Domain\Repositories\TagRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class CreatePollHandler
{
    public function __construct(
        private PollRepositoryInterface $pollRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private TagRepositoryInterface $tagRepository,
    ) {}

    public function execute(PollDTO $dto): Poll
    {
        return DB::transaction(function () use ($dto) {
            $options = collect($dto->options)
                ->map(fn (array $opt) => array_merge($opt, ['votes' => 0]))
                ->toArray();

            $status = $dto->workflowAction === 'submit_review' ? 'pending_review' : 'draft';

            $poll = Poll::create(
                question: $dto->question,
                options: $options,
                isActive: $dto->isActive,
                status: $status,
                expiresAt: $dto->expiresAt,
                scheduledAt: $dto->scheduledAt,
                archiveAt: $dto->archiveAt,
            );

            $poll = $this->pollRepository->save($poll);

            $this->categoryRepository->syncForContent($poll->id(), Poll::class, $dto->categoryIds);
            $this->tagRepository->syncForContent($poll->id(), Poll::class, $dto->tagIds);

            return $poll;
        });
    }
}
