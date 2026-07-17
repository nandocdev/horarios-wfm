<?php

declare(strict_types=1);

namespace App\Shared\Support\Communications;

use Illuminate\Database\Eloquent\Model;

final class ContentStateMachine
{
    private const TRANSITIONS = [
        'draft' => ['pending_review'],
        'pending_review' => ['published', 'draft'],
        'published' => ['archived'],
        'archived' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function validTransitions(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public static function assertCanTransition(Model $model, string $to): void
    {
        $from = $model->status;

        if ($from === $to) {
            return;
        }

        if (! self::canTransition($from, $to)) {
            throw new \RuntimeException(sprintf(
                'Transición de estado inválida: "%s" → "%s". Las transiciones permitidas desde "%s" son: %s.',
                $from,
                $to,
                $from,
                implode(', ', self::validTransitions($from)) ?: 'ninguna'
            ));
        }
    }
}
