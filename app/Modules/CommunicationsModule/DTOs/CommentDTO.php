<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\DTOs;

/**
 * Datos de entrada validados para crear o actualizar un comentario.
 */
readonly class CommentDTO
{
    public function __construct(
        public string $content,
        public ?int $parentId = null,
    ) {}

    /**
     * Construye el DTO desde un array validado (Form Request).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            parentId: $data['parent_id'] ?? null,
        );
    }
}
