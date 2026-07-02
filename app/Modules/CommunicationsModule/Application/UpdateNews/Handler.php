<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\UpdateNews;

use App\Modules\CommunicationsModule\Domain\Aggregates\News;
use App\Modules\CommunicationsModule\Domain\Repositories\NewsRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ContentBody;
use App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange;
use App\Modules\CommunicationsModule\Domain\ValueObjects\NewsContent;
use App\Modules\CommunicationsModule\Domain\ValueObjects\Slug;
use DateTimeImmutable;
use RuntimeException;

final readonly class Handler
{
    public function __construct(
        private NewsRepository $repository,
    ) {}

    public function __invoke(Command $command): News
    {
        $news = $this->repository->findById($command->newsId);

        if ($news === null) {
            throw new RuntimeException("News not found: {$command->newsId}");
        }

        $updated = News::draft(
            content: new NewsContent(
                title: $command->title,
                slug: new Slug($command->slug),
                body: new ContentBody($command->content),
                excerpt: $command->excerpt,
            ),
            authorId: $news->authorId(),
            dateRange: new DateRange(
                scheduledAt: $command->scheduledAt ? new DateTimeImmutable($command->scheduledAt) : null,
                archiveAt: $command->archiveAt ? new DateTimeImmutable($command->archiveAt) : null,
            ),
            isActive: $command->isActive,
        );
        $updated->setCategoryIds($command->categoryIds);
        $updated->setTagIds($command->tagIds);

        $this->repository->save($updated);

        return $updated;
    }
}
