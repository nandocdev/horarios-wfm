<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\CategoryDTO;
use App\Src\Platform\Domain\Entities\Category;
use App\Src\Platform\Domain\Repositories\CategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function execute(int $categoryId, CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($categoryId, $dto) {
            $category = $this->categoryRepository->findById($categoryId);

            if (! $category) {
                throw new \RuntimeException("Category with ID {$categoryId} not found.");
            }

            $slugExists = $this->categoryRepository->findBySlug($dto->slug);
            if ($slugExists && $slugExists->id() !== $categoryId) {
                throw new \RuntimeException("Another category with slug '{$dto->slug}' already exists.");
            }

            $category = Category::create(
                name: $dto->name,
                slug: $dto->slug,
                description: $dto->description,
                color: $dto->color,
                isActive: $dto->isActive,
                sortOrder: $dto->sortOrder,
            );

            return $this->categoryRepository->save($category);
        });
    }
}
