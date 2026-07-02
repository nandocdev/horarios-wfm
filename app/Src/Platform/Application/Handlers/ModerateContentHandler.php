<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Services\ContentModerationService;
use App\Src\Platform\Application\DTOs\ModerationDTO;
use Illuminate\Database\Eloquent\Model;

final readonly class ModerateContentHandler
{
    public function __construct(
        private ContentModerationService $moderationService,
    ) {}

    public function execute(Model $content, ModerationDTO $dto): Model
    {
        return $this->moderationService->moderate($content, $dto);
    }

    public function approve(Model $content, ?string $notes = null): Model
    {
        return $this->execute($content, ModerationDTO::approve($notes));
    }

    public function reject(Model $content, string $notes): Model
    {
        return $this->execute($content, ModerationDTO::reject($notes));
    }

    public function submitForReview(Model $content): Model
    {
        return $this->execute($content, ModerationDTO::submitForReview());
    }

    public function archive(Model $content): Model
    {
        return $this->execute($content, ModerationDTO::archive());
    }
}
