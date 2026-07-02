<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\NewsDTO;
use App\Src\Platform\Domain\Entities\News;
use App\Src\Platform\Domain\Repositories\CategoryRepositoryInterface;
use App\Src\Platform\Domain\Repositories\NewsRepositoryInterface;
use App\Src\Platform\Domain\Repositories\TagRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class CreateNewsHandler
{
    public function __construct(
        private NewsRepositoryInterface $newsRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private TagRepositoryInterface $tagRepository,
    ) {}

    public function execute(NewsDTO $dto): News
    {
        return DB::transaction(function () use ($dto) {
            $status = $dto->workflowAction === 'submit_review' ? 'pending_review' : 'draft';

            $news = News::create(
                title: $dto->title,
                slug: $dto->slug,
                excerpt: $dto->excerpt,
                content: $dto->content,
                authorId: $dto->authorId,
                isActive: $dto->isActive,
                status: $status,
                publishedAt: $dto->publishedAt,
                scheduledAt: $dto->scheduledAt,
                archiveAt: $dto->archiveAt,
            );

            $news = $this->newsRepository->save($news);

            $this->categoryRepository->syncForContent($news->id(), News::class, $dto->categoryIds);
            $this->tagRepository->syncForContent($news->id(), News::class, $dto->tagIds);

            return $news;
        });
    }
}
