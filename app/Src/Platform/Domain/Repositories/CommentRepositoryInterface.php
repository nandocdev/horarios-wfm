<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Repositories;

use App\Src\Platform\Domain\Entities\Comment;

interface CommentRepositoryInterface {
    public function save(Comment $comment): Comment;

    public function findById(int $id): ?Comment;

    public function findByNews(int $newsId, int $perPage = 25): array;

    public function findByUser(int $userId, int $perPage = 25): array;

    public function findReplies(int $parentId, int $perPage = 25): array;

    public function countByNews(int $newsId): int;

    public function delete(Comment $comment): void;
}
