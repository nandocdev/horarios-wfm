<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Observers;

use App\Modules\CommunicationsModule\Models\Poll;
use Illuminate\Support\Facades\Cache;

/**
 * Observador para el modelo Poll.
 * Maneja efectos secundarios del ciclo de vida.
 */
class PollObserver
{
    public function created(Poll $poll): void
    {
        $this->clearPollsCache();
    }

    public function updated(Poll $poll): void
    {
        $this->clearPollsCache();
        Cache::forget("poll:{$poll->id}");
    }

    public function deleted(Poll $poll): void
    {
        $this->clearPollsCache();
        Cache::forget("poll:{$poll->id}");
    }

    private function clearPollsCache(): void
    {
        Cache::forget('polls_active');
        Cache::forget('polls_recent');
    }
}
