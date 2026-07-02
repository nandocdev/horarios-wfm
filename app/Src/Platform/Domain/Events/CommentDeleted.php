<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Events;

use App\Src\Platform\Domain\Entities\Comment;

final readonly class CommentDeleted {
    public function __construct(
        public Comment $comment,
    ) {
    }
}
