<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Events;

use App\Src\Platform\Domain\Entities\Mention;

final readonly class MentionCreated {
    public function __construct(
        public Mention $mention,
    ) {
    }
}
