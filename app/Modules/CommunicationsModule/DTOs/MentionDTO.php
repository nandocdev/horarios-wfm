<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\DTOs;

/**
 * Datos de entrada validados para crear una mención.
 */
readonly class MentionDTO
{
    public function __construct(
        public int $mentionedUserId,
        public string $context,
    ) {}

    /**
     * Construye el DTO desde un array validado (Form Request).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mentionedUserId: $data['mentioned_user_id'],
            context: $data['context'],
        );
    }
}
