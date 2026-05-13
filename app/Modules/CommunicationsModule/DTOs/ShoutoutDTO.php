<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\DTOs;

/**
 * DTO para la transferencia de datos de Shoutouts.
 */
readonly class ShoutoutDTO
{
    public function __construct(
        public int $employee_id,
        public string $message,
        public bool $is_active,
        public ?string $scheduled_at = null,
        public ?string $archive_at = null,
        public array $category_ids = [],
        public array $tag_ids = [],
        public string $workflow_action = 'save_draft',
        public mixed $image = null,
    ) {}

    /**
     * Construye el DTO desde un array validado.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            employee_id: $data['employee_id'],
            message: $data['message'],
            is_active: $data['is_active'] ?? true,
            scheduled_at: $data['scheduled_at'] ?? null,
            archive_at: $data['archive_at'] ?? null,
            category_ids: $data['category_ids'] ?? [],
            tag_ids: $data['tag_ids'] ?? [],
            workflow_action: $data['workflow_action'] ?? 'save_draft',
        );
    }
}
