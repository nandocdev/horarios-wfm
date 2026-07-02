<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Mappers;

use App\Src\Platform\Domain\Entities\Reaction;
use App\Src\Platform\Domain\ValueObjects\ReactionType;
use App\Src\Platform\Infrastructure\Persistence\EloquentReaction;
use DateTimeImmutable;

final class ReactionMapper
{
    public static function toDomain(EloquentReaction $eloquent): Reaction
    {
        return Reaction::fromDatabase(
            id: $eloquent->id,
            shoutoutId: $eloquent->shoutout_id,
            userId: $eloquent->user_id,
            type: ReactionType::from($eloquent->type),
            isActive: (bool) $eloquent->is_active,
            createdAt: new DateTimeImmutable($eloquent->created_at),
            updatedAt: new DateTimeImmutable($eloquent->updated_at),
        );
    }

    public static function toArray(Reaction $reaction): array
    {
        return [
            'id' => $reaction->id(),
            'shoutout_id' => $reaction->shoutoutId(),
            'user_id' => $reaction->userId(),
            'type' => $reaction->type()->value,
            'is_active' => $reaction->isActive(),
            'created_at' => $reaction->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $reaction->updatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function toEloquent(Reaction $reaction): array
    {
        return [
            'shoutout_id' => $reaction->shoutoutId(),
            'user_id' => $reaction->userId(),
            'type' => $reaction->type()->value,
            'is_active' => $reaction->isActive(),
        ];
    }
}
