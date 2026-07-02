<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Application\Mappers\CommentMapper;
use App\Src\Platform\Domain\Entities\Comment;
use App\Src\Platform\Domain\Repositories\CommentRepositoryInterface;

final class EloquentCommentRepository implements CommentRepositoryInterface {
    public function save(Comment $comment): Comment {
        $eloquent = EloquentComment::updateOrCreate(
            ['id' => $comment->id()],
            CommentMapper::toEloquent($comment),
        );

        return CommentMapper::toDomain($eloquent);
    }

    public function findById(int $id): ?Comment {
        $eloquent = EloquentComment::with(['user', 'parent'])->find($id);

        if ($eloquent === null) {
            return null;
        }

        return CommentMapper::toDomain($eloquent);
    }

    public function findByNews(int $newsId, int $perPage = 25): array {
        $query = EloquentComment::with(['user', 'replies'])
            ->where('news_id', $newsId)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentComment $eloquent) => CommentMapper::toDomain($eloquent))
            ->items();
    }

    public function findByUser(int $userId, int $perPage = 25): array {
        $query = EloquentComment::with(['news'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentComment $eloquent) => CommentMapper::toDomain($eloquent))
            ->items();
    }

    public function findReplies(int $parentId, int $perPage = 25): array {
        $query = EloquentComment::with(['user'])
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('created_at', 'asc');

        return $query->paginate($perPage)
            ->through(fn(EloquentComment $eloquent) => CommentMapper::toDomain($eloquent))
            ->items();
    }

    public function countByNews(int $newsId): int {
        return EloquentComment::where('news_id', $newsId)
            ->where('is_active', true)
            ->count();
    }

    public function delete(Comment $comment): void {
        EloquentComment::where('id', $comment->id())->delete();
    }
}
