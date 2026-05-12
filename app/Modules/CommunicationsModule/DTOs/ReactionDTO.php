<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\DTOs;

use App\Modules\CommunicationsModule\Enums\ReactionType;

/**
 * Datos de entrada validados para crear una reacción.
 */
readonly class ReactionDTO
{
    public function __construct(
        public ReactionType $type,
    ) {}

    /**
     * Construye el DTO desde un array validado (Form Request).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: ReactionType::from($data['type']),
        );
    }
}
