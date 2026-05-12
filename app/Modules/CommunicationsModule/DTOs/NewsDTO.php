<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\DTOs;

use Illuminate\Http\UploadedFile;

/**
 * DTO para la transferencia de datos de Noticias.
 */
class NewsDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $excerpt,
        public readonly string $content,
        public readonly string $published_at,
        public readonly ?string $scheduled_at,
        public readonly ?string $archive_at,
        /** @var int[] */
        public readonly array $categoryIds,
        /** @var int[] */
        public readonly array $tagIds,
        public readonly string $workflowAction,
        public readonly bool $is_active,
        public readonly int $author_id,
        /** @var UploadedFile|null */
        public readonly mixed $featuredImage = null,
        /** @var UploadedFile[] */
        public readonly array $attachments = []
    ) {}

    /**
     * Construye el DTO desde un array validado.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            slug: $data['slug'],
            excerpt: $data['excerpt'] ?? null,
            content: $data['content'],
            published_at: $data['published_at'] ?? now()->toDateTimeString(),
            scheduled_at: $data['scheduled_at'] ?? null,
            archive_at: $data['archive_at'] ?? null,
            categoryIds: array_map('intval', $data['category_ids'] ?? []),
            tagIds: array_map('intval', $data['tag_ids'] ?? []),
            workflowAction: $data['workflow_action'] ?? 'save_draft',
            is_active: $data['is_active'] ?? true,
            author_id: $data['author_id'] ?? auth()->id(),
            featuredImage: $data['featured_image'] ?? null,
            attachments: $data['attachments'] ?? []
        );
    }
}
