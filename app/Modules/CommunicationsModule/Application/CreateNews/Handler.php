<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\CreateNews;

use App\Modules\CommunicationsModule\Domain\Aggregates\News;
use App\Modules\CommunicationsModule\Domain\Repositories\NewsRepository;
use App\Modules\CommunicationsModule\Domain\Services\MentionParser;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ContentBody;
use App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange;
use App\Modules\CommunicationsModule\Domain\ValueObjects\NewsContent;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use App\Modules\CommunicationsModule\Domain\ValueObjects\Slug;
use DateTimeImmutable;

final readonly class Handler
{
    public function __construct(
        private NewsRepository $repository,
        private MentionParser $mentionParser,
    ) {}

    public function __invoke(Command $command): News
    {
        $scheduledAt = $command->scheduledAt ? new DateTimeImmutable($command->scheduledAt) : null;
        $archiveAt = $command->archiveAt ? new DateTimeImmutable($command->archiveAt) : null;

        $newsContent = new NewsContent(
            title: $command->title,
            slug: new Slug($command->slug),
            body: new ContentBody($command->content),
            excerpt: $command->excerpt,
        );

        $news = $command->workflowAction === 'submit_review'
            ? News::submitForReview(
                content: $newsContent,
                authorId: new PersonId($command->authorId),
                dateRange: new DateRange($scheduledAt, $archiveAt),
                isActive: $command->isActive,
            )
            : News::draft(
                content: $newsContent,
                authorId: new PersonId($command->authorId),
                dateRange: new DateRange($scheduledAt, $archiveAt),
                isActive: $command->isActive,
            );

        $news->setCategoryIds($command->categoryIds);
        $news->setTagIds($command->tagIds);

        $this->repository->save($news);

        return $news;
    }
}
