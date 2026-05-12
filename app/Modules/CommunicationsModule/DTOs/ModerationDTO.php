<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\DTOs;

/**
 * Datos para operaciones de moderación de contenido.
 */
readonly class ModerationDTO
{
    public function __construct(
        public string $status,
        public ?int $approvedBy = null,
        public ?string $moderationNotes = null,
    ) {}

    /**
     * Construye el DTO desde un array validado.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'],
            approvedBy: $data['approved_by'] ?? null,
            moderationNotes: $data['moderation_notes'] ?? null,
        );
    }

    /**
     * DTO para aprobación.
     */
    public static function approve(?string $notes = null): self
    {
        return new self(
            status: 'published',
            approvedBy: auth()->id(),
            moderationNotes: $notes,
        );
    }

    /**
     * DTO para rechazo.
     */
    public static function reject(string $notes): self
    {
        return new self(
            status: 'draft',
            approvedBy: auth()->id(),
            moderationNotes: $notes,
        );
    }

    /**
     * DTO para envío a revisión.
     */
    public static function submitForReview(): self
    {
        return new self(status: 'pending_review');
    }

    /**
     * DTO para archivar.
     */
    public static function archive(): self
    {
        return new self(status: 'archived');
    }
}
