<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Observers;

use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Support\Facades\Cache;

/**
 * Observador para el modelo Shoutout.
 * Maneja efectos secundarios del ciclo de vida.
 */
class ShoutoutObserver
{
    public function created(Shoutout $shoutout): void
    {
        $this->clearShoutoutsCache();
    }

    public function updated(Shoutout $shoutout): void
    {
        $this->clearShoutoutsCache();
        Cache::forget("shoutout:{$shoutout->id}");
    }

    public function deleted(Shoutout $shoutout): void
    {
        $this->clearShoutoutsCache();
        Cache::forget("shoutout:{$shoutout->id}");
    }

    private function clearShoutoutsCache(): void
    {
        Cache::forget('shoutouts_list');
        Cache::forget('shoutouts_recent');
    }
}
