<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\ValueObjects;

final readonly class PollOption
{
    public function __construct(
        private string $label,
        private string $value,
        private ?HexColor $color = null,
        private int $votes = 0,
    ) {
        if (empty($this->label)) {
            throw new \InvalidArgumentException('Poll option label cannot be empty');
        }
    }

    public function label(): string
    {
        return $this->label;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function color(): ?HexColor
    {
        return $this->color;
    }

    public function votes(): int
    {
        return $this->votes;
    }

    public function incrementVotes(): self
    {
        return new self($this->label, $this->value, $this->color, $this->votes + 1);
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'color' => $this->color?->value(),
            'votes' => $this->votes,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'],
            value: $data['value'],
            color: isset($data['color']) ? new HexColor($data['color']) : null,
            votes: (int) ($data['votes'] ?? 0),
        );
    }
}
