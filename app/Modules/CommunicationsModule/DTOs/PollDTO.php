<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\DTOs;

/**
 * DTO para la transferencia de datos de Encuestas.
 */
readonly class PollDTO
{
    public function __construct(
        public string $question,
        public array $options,
        public bool $is_active,
        public ?string $expires_at = null,
        public ?string $scheduled_at = null,
        public ?string $archive_at = null,
    ) {}

    /**
     * Construye el DTO desde un array validado.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            question: $data['question'],
            options: $data['options'],
            is_active: $data['is_active'] ?? true,
            expires_at: $data['expires_at'] ?? null,
            scheduled_at: $data['scheduled_at'] ?? null,
            archive_at: $data['archive_at'] ?? null,
        );
    }
}
