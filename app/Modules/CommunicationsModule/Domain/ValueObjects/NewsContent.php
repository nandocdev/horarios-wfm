<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Domain\ValueObjects;

final readonly class NewsContent
{
    public function __construct(
        private string $title,
        private Slug $slug,
        private ContentBody $body,
        private ?string $excerpt = null,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function body(): ContentBody
    {
        return $this->body;
    }

    public function excerpt(): ?string
    {
        return $this->excerpt ?? $this->body->excerpt();
    }
}
