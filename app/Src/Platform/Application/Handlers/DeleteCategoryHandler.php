<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Repositories\CategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function execute(int $categoryId): void
    {
        DB::transaction(function () use ($categoryId) {
            $category = $this->categoryRepository->findById($categoryId);

            if (! $category) {
                throw new \RuntimeException("Category with ID {$categoryId} not found.");
            }

            $this->categoryRepository->delete($category);
        });
    }
}
