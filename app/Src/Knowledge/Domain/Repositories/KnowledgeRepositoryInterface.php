<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Domain\Repositories;

use App\Src\Knowledge\Domain\Entities\Article;
use App\Src\Knowledge\Domain\Entities\Category;

interface KnowledgeRepositoryInterface
{
    public function saveArticle(Article $article): Article;
    public function findArticleById(int $id): ?Article;
    public function searchArticles(?string $query = null, ?int $categoryId = null, ?string $tag = null, ?string $status = null): array;
    public function deleteArticle(int $id): void;

    public function findAllCategories(): array;
    public function findCategoryById(int $id): ?Category;
}
