<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final readonly class NewsDTO
{
    public function __construct(
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $content,
        public string $publishedAt,
        public ?string $scheduledAt,
        public ?string $archiveAt,
        public array $categoryIds,
        public array $tagIds,
        public string $workflowAction,
        public bool $isActive,
        public int $authorId,
        public mixed $featuredImage = null,
        public array $attachments = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            slug: $data['slug'],
            excerpt: $data['excerpt'] ?? null,
            content: $data['content'],
            publishedAt: $data['published_at'] ?? now()->toDateTimeString(),
            scheduledAt: $data['scheduled_at'] ?? null,
            archiveAt: $data['archive_at'] ?? null,
            categoryIds: array_map('intval', $data['category_ids'] ?? []),
            tagIds: array_map('intval', $data['tag_ids'] ?? []),
            workflowAction: $data['workflow_action'] ?? 'save_draft',
            isActive: $data['is_active'] ?? true,
            authorId: $data['author_id'] ?? auth()->id(),
            featuredImage: $data['featured_image'] ?? null,
            attachments: $data['attachments'] ?? [],
        );
    }
}
