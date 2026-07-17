<?php

declare(strict_types=1);

namespace App\Shared\Support\Communications;

use App\Modules\CoreModule\Models\User;

trait HasContentState
{
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending_review'], true);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function transition(string $to): void
    {
        ContentStateMachine::assertCanTransition($this, $to);
        $this->update(['status' => $to]);
    }

    public function submitForReview(): void
    {
        ContentStateMachine::assertCanTransition($this, 'pending_review');
        $this->update(['status' => 'pending_review']);
    }

    public function approve(User $moderator, ?string $notes = null): void
    {
        ContentStateMachine::assertCanTransition($this, 'published');
        $this->update([
            'status' => 'published',
            'approved_by' => $moderator->id,
            'approved_at' => now(),
            'moderation_notes' => $notes,
        ]);
    }

    public function reject(User $moderator, string $notes): void
    {
        ContentStateMachine::assertCanTransition($this, 'draft');
        $this->update([
            'status' => 'draft',
            'approved_by' => $moderator->id,
            'approved_at' => now(),
            'moderation_notes' => $notes,
        ]);
    }

    public function archive(): void
    {
        ContentStateMachine::assertCanTransition($this, 'archived');
        $this->update(['status' => 'archived']);
    }
}
