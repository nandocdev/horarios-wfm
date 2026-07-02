<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Application\Handlers;

use App\Src\Knowledge\Application\DTOs\ArticleDTO;
use App\Src\Knowledge\Domain\Entities\Article;
use App\Src\Knowledge\Domain\Repositories\KnowledgeRepositoryInterface;

final class CreateArticleHandler
{
    public function __construct(
        private KnowledgeRepositoryInterface $repository,
    ) {}

    public function handle(ArticleDTO $dto, int $userId): Article
    {
        $article = Article::create(
            title: $dto->title,
            content: $dto->content,
            createdBy: $userId,
            summary: $dto->summary,
            categoryId: $dto->categoryId,
            status: $dto->status,
            publishedAt: $dto->publishedAt,
            expiresAt: $dto->expiresAt,
            tagNames: $dto->tagNames,
            queueIds: $dto->queueIds,
        );

        return $this->repository->saveArticle($article);
    }
}
