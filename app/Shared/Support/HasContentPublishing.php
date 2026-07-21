<?php

declare(strict_types=1);

namespace App\Shared\Support;

trait HasContentPublishing
{
    public function isPublished(): bool
    {
        return $this->status === 'published'
            || (bool) ($this->is_published ?? false);
    }

    public function canBeEdited(): bool
    {
        if (in_array('status', $this->getFillable())) {
            return in_array($this->status, ['draft', 'pending_review', 'review']);
        }

        return ! $this->isPublished();
    }

    public function statusLabel(): string
    {
        if (in_array('status', $this->getFillable())) {
            return match ($this->status) {
                'draft' => 'Borrador',
                'review', 'pending_review' => 'En Revisión',
                'published' => 'Publicado',
                'archived' => 'Archivado',
                default => $this->status,
            };
        }

        return $this->is_published ? 'Publicado' : 'Borrador';
    }

    public function publish(): void
    {
        if (in_array('status', $this->getFillable())) {
            $this->status = 'published';
            if (in_array('published_at', $this->getFillable()) && ! $this->published_at) {
                $this->published_at = now();
            }
        } else {
            $this->is_published = true;
        }

        $this->save();
    }

    public function archive(): void
    {
        if (in_array('status', $this->getFillable())) {
            $this->status = 'archived';
        } else {
            $this->is_published = false;
        }

        $this->save();
    }

    public function submitForReview(): void
    {
        if (in_array('status', $this->getFillable())) {
            $this->status = 'pending_review';
            $this->save();
        }
    }
}
