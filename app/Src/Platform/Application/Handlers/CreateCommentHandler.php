<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\CommentDTO;
use App\Src\Platform\Domain\Entities\Comment;
use App\Src\Platform\Domain\Entities\News;
use App\Src\Platform\Domain\Events\CommentCreated;
use App\Src\Platform\Domain\Repositories\CommentRepositoryInterface;
use App\Src\Platform\Domain\Repositories\NewsRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class CreateCommentHandler
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private NewsRepositoryInterface $newsRepository,
    ) {}

    public function execute(CommentDTO $dto, int $newsId, int $userId): Comment
    {
        return DB::transaction(function () use ($dto, $newsId, $userId) {
            $news = $this->newsRepository->findById($newsId);

            if (! $news) {
                throw new \RuntimeException("News with ID {$newsId} not found.");
            }

            if ($dto->parentId !== null) {
                $parentComment = $this->commentRepository->findById($dto->parentId);

                if (! $parentComment) {
                    throw new \RuntimeException("Parent comment not found.");
                }
            }

            $comment = Comment::create(
                newsId: $newsId,
                userId: $userId,
                content: $dto->content,
                parentId: $dto->parentId,
                isActive: true,
            );

            $comment = $this->commentRepository->save($comment);

            event(new CommentCreated($comment));

            return $comment;
        });
    }
}
