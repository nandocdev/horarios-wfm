<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Entities\Mention;
use App\Src\Platform\Domain\Events\MentionCreated;
use App\Src\Platform\Domain\Repositories\MentionRepositoryInterface;
use App\Src\Platform\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final readonly class ProcessMentionsHandler
{
    public function __construct(
        private MentionRepositoryInterface $mentionRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(string $content, Model $mentionable, int $mentionerUserId): array
    {
        $mentions = [];

        preg_match_all('/@(\w+)/', $content, $matches);

        if (empty($matches[1])) {
            return $mentions;
        }

        $mentionedUsernames = array_unique($matches[1]);

        DB::transaction(function () use ($mentionedUsernames, $mentionable, $mentionerUserId, &$mentions) {
            foreach ($mentionedUsernames as $username) {
                $user = $this->userRepository->findByUsername($username);

                if ($user && $user->id() !== $mentionerUserId) {
                    $mention = Mention::create(
                        mentionedUserId: $user->id(),
                        mentionerUserId: $mentionerUserId,
                        mentionableType: get_class($mentionable),
                        mentionableId: $mentionable->id,
                        context: $this->extractContext($content, $username),
                        isRead: false,
                    );

                    $mention = $this->mentionRepository->save($mention);
                    event(new MentionCreated($mention));
                    $mentions[] = $mention;
                }
            }
        });

        return $mentions;
    }

    private function extractContext(string $content, string $username): string
    {
        $mention = "@{$username}";
        $position = strpos($content, $mention);

        if ($position === false) {
            return '';
        }

        $start = max(0, $position - 50);
        $end = min(strlen($content), $position + strlen($mention) + 50);

        $context = substr($content, $start, $end - $start);

        if ($start > 0) {
            $context = '...' . $context;
        }
        if ($end < strlen($content)) {
            $context .= '...';
        }

        return $context;
    }
}
