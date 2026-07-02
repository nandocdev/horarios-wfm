<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use App\Src\Platform\Application\Mappers\ReactionMapper;
use App\Src\Platform\Domain\Entities\Reaction;
use App\Src\Platform\Domain\Repositories\ReactionRepositoryInterface;
use App\Src\Platform\Domain\ValueObjects\ReactionType;

final class EloquentReactionRepository implements ReactionRepositoryInterface {
    public function save(Reaction $reaction): Reaction {
        $eloquent = EloquentReaction::updateOrCreate(
            ['id' => $reaction->id()],
            ReactionMapper::toEloquent($reaction),
        );

        return ReactionMapper::toDomain($eloquent);
    }

    public function findById(int $id): ?Reaction {
        $eloquent = EloquentReaction::with(['user'])->find($id);

        if ($eloquent === null) {
            return null;
        }

        return ReactionMapper::toDomain($eloquent);
    }

    public function findByShoutout(int $shoutoutId, int $perPage = 25): array {
        $query = EloquentReaction::with(['user'])
            ->where('shoutout_id', $shoutoutId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)
            ->through(fn(EloquentReaction $eloquent) => ReactionMapper::toDomain($eloquent))
            ->items();
    }

    public function findByUserAndShoutout(int $userId, int $shoutoutId): ?Reaction {
        $eloquent = EloquentReaction::where('shoutout_id', $shoutoutId)
            ->where('user_id', $userId)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return ReactionMapper::toDomain($eloquent);
    }

    public function countByShoutout(int $shoutoutId): int {
        return EloquentReaction::where('shoutout_id', $shoutoutId)
            ->where('is_active', true)
            ->count();
    }

    public function countByType(int $shoutoutId, ReactionType $type): int {
        return EloquentReaction::where('shoutout_id', $shoutoutId)
            ->where('type', $type->value)
            ->where('is_active', true)
            ->count();
    }

    public function delete(Reaction $reaction): void {
        EloquentReaction::where('id', $reaction->id())->delete();
    }
}
