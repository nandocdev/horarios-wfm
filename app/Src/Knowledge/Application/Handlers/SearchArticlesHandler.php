<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Application\Handlers;

use App\Src\Knowledge\Application\DTOs\ArticleSearchDTO;
use App\Src\Knowledge\Domain\Repositories\KnowledgeRepositoryInterface;

final class SearchArticlesHandler
{
    public function __construct(
        private KnowledgeRepositoryInterface $repository,
    ) {}

    public function handle(ArticleSearchDTO $dto): array
    {
        return $this->repository->searchArticles(
            query: $dto->query,
            categoryId: $dto->categoryId,
            tag: $dto->tag,
            status: $dto->status,
        );
    }
}
