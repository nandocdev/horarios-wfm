<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeModule\DTOs;

use Carbon\CarbonImmutable;

/**
 * DTO para la transferencia de datos de artículos de forma aislada y tipada.
 */
readonly class ArticleDTO
{
    /**
     * @param int[] $queues
     * @param string[] $tags
     */
    public function __construct(
        public string $title,
        public ?string $summary,
        public string $content,
        public ?int $category_id,
        public string $status,
        public ?CarbonImmutable $published_at,
        public ?CarbonImmutable $expires_at,
        public array $queues,
        public array $tags,
    ) {}

    /**
     * Construye el DTO a partir de un array de datos validados.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) $data['title'],
            summary: !empty($data['summary']) ? (string) $data['summary'] : null,
            content: (string) $data['content'],
            category_id: !empty($data['category_id']) ? (int) $data['category_id'] : null,
            status: (string) ($data['status'] ?? 'draft'),
            published_at: !empty($data['published_at']) ? CarbonImmutable::parse($data['published_at']) : null,
            expires_at: !empty($data['expires_at']) ? CarbonImmutable::parse($data['expires_at']) : null,
            queues: array_map('intval', (array) ($data['queues'] ?? [])),
            tags: array_filter(array_map('trim', (array) ($data['tags'] ?? []))),
        );
    }
}
/**
 * [RIESGOS]
 * - Fechas mal formateadas → CarbonImmutable lanzará una excepción si el string no es parseable. La validación en el formulario debe asegurar el formato adecuado antes de la construcción.
 */
