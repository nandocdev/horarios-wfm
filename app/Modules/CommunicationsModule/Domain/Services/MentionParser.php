<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\Services;

use App\Modules\CommunicationsModule\Domain\Aggregates\Mention;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ContentBody;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;

final class MentionParser
{
    /**
     * @param callable(string): ?int $lookupUserId
     * @return Mention[]
     */
    public function parse(ContentBody $content, PersonId $mentionerId, callable $lookupUserId): array
    {
        $usernames = $content->extractUsernames();
        $mentions = [];

        foreach ($usernames as $username) {
            $mentionedUserId = $lookupUserId($username);

            if ($mentionedUserId === null || $mentionedUserId === $mentionerId->value()) {
                continue;
            }

            $cursor = mb_strpos($content->value(), '@'.$username);
            $context = $cursor !== false ? $content->contextSnippet($cursor) : $username;

            $mentions[] = new Mention(
                mentionedUserId: new PersonId($mentionedUserId),
                mentionerUserId: $mentionerId,
                context: $context,
            );
        }

        return $mentions;
    }
}
