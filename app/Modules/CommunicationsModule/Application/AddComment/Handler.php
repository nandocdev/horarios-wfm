<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Application\AddComment;

use App\Modules\CommunicationsModule\Domain\Entities\Comment;
use App\Modules\CommunicationsModule\Domain\Repositories\NewsRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ContentBody;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use RuntimeException;

final readonly class Handler
{
    public function __construct(
        private NewsRepository $repository,
    ) {}

    public function __invoke(Command $command): Comment
    {
        $news = $this->repository->findById($command->newsId);

        if ($news === null) {
            throw new RuntimeException("News not found: {$command->newsId}");
        }

        $comment = new Comment(
            content: new ContentBody($command->content),
            authorId: new PersonId($command->userId),
            parentId: $command->parentId,
        );

        $news->addComment($comment);
        $this->repository->save($news);

        return $comment;
    }
}
