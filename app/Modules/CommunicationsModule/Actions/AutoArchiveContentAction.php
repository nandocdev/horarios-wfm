<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Support\Facades\DB;

/**
 * Archiva contenido automáticamente cuando archive_at vence.
 */
class AutoArchiveContentAction
{
    /**
     * @return array{news:int,polls:int,shoutouts:int}
     */
    public function execute(): array
    {
        return DB::transaction(function (): array {
            $now = now();

            $newsArchived = News::query()
                ->where('status', '!=', 'archived')
                ->whereNotNull('archive_at')
                ->where('archive_at', '<=', $now)
                ->update([
                    'status' => 'archived',
                    'is_active' => false,
                    'updated_at' => $now,
                ]);

            $pollsArchived = Poll::query()
                ->where('status', '!=', 'archived')
                ->whereNotNull('archive_at')
                ->where('archive_at', '<=', $now)
                ->update([
                    'status' => 'archived',
                    'is_active' => false,
                    'updated_at' => $now,
                ]);

            $shoutoutsArchived = Shoutout::query()
                ->where('status', '!=', 'archived')
                ->whereNotNull('archive_at')
                ->where('archive_at', '<=', $now)
                ->update([
                    'status' => 'archived',
                    'is_active' => false,
                    'updated_at' => $now,
                ]);

            return [
                'news' => $newsArchived,
                'polls' => $pollsArchived,
                'shoutouts' => $shoutoutsArchived,
            ];
        });
    }
}
