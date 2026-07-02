<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Events;

use App\Src\Platform\Domain\Entities\Reaction;

final readonly class ReactionAdded {
    public function __construct(
        public Reaction $reaction,
    ) {
    }
}
