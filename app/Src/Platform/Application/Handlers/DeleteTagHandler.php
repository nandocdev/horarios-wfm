<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Repositories\TagRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class DeleteTagHandler
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
    ) {}

    public function execute(int $tagId): void
    {
        DB::transaction(function () use ($tagId) {
            $tag = $this->tagRepository->findById($tagId);

            if (! $tag) {
                throw new \RuntimeException("Tag with ID {$tagId} not found.");
            }

            $this->tagRepository->delete($tag);
        });
    }
}
