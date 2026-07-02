<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Application\Mappers\MentionMapper;
use App\Src\Platform\Domain\Entities\Mention;
use App\Src\Platform\Domain\Repositories\MentionRepositoryInterface;

final class EloquentMentionRepository implements MentionRepositoryInterface {
    public function save(Mention $mention): Mention {
        $eloquent = EloquentMention::updateOrCreate(
            ['id' => $mention->id()],
            MentionMapper::toEloquent($mention),
        );

        return MentionMapper::toDomain($eloquent);
    }

    public function findById(int $id): ?Mention {
        $eloquent = EloquentMention::with(['mentionedUser', 'mentionerUser'])->find($id);

        if ($eloquent === null) {
            return null;
        }

        return MentionMapper::toDomain($eloquent);
    }

    public function findByMentionedUser(int $userId, int $perPage = 25): array {
        $query = EloquentMention::with(['mentionedUser', 'mentionerUser'])
            ->where('mentioned_user_id', $userId)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentMention $eloquent) => MentionMapper::toDomain($eloquent))
            ->items();
    }

    public function findByMentioner(int $userId, int $perPage = 25): array {
        $query = EloquentMention::with(['mentionedUser', 'mentionerUser'])
            ->where('mentioner_user_id', $userId)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentMention $eloquent) => MentionMapper::toDomain($eloquent))
            ->items();
    }

    public function findUnreadByUser(int $userId, int $perPage = 25): array {
        $query = EloquentMention::with(['mentionedUser', 'mentionerUser'])
            ->where('mentioned_user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentMention $eloquent) => MentionMapper::toDomain($eloquent))
            ->items();
    }

    public function findByMentionable(string $mentionableType, int $mentionableId): array {
        return EloquentMention::with(['mentionedUser', 'mentionerUser'])
            ->where('mentionable_type', $mentionableType)
            ->where('mentionable_id', $mentionableId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(EloquentMention $eloquent) => MentionMapper::toDomain($eloquent))
            ->toArray();
    }

    public function countUnreadByUser(int $userId): int {
        return EloquentMention::where('mentioned_user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead(int $id): void {
        EloquentMention::where('id', $id)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAllAsRead(int $userId): void {
        EloquentMention::where('mentioned_user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function delete(Mention $mention): void {
        EloquentMention::where('id', $mention->id())->delete();
    }
}
