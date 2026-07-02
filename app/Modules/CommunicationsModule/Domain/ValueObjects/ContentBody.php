<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\ValueObjects;

final readonly class ContentBody
{
    public function __construct(
        private string $value
    ) {
        if (mb_strlen($this->value) < 1) {
            throw new \InvalidArgumentException('Content body cannot be empty');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function extractUsernames(): array
    {
        preg_match_all('/@(\w+)/', $this->value, $matches);

        return array_unique($matches[1] ?? []);
    }

    public function excerpt(int $length = 50): string
    {
        $cleaned = strip_tags($this->value);

        return mb_strlen($cleaned) > $length
            ? mb_substr($cleaned, 0, $length).'…'
            : $cleaned;
    }

    public function contextSnippet(int $cursor, int $radius = 50): string
    {
        $start = max(0, $cursor - $radius);

        return mb_substr($this->value, $start, $radius * 2);
    }
}
