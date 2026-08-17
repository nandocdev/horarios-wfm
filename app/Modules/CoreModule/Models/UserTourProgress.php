<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'tour_key',
    'version',
    'state',
    'seen_at',
])]
class UserTourProgress extends Model
{
    protected $casts = [
        'version' => 'integer',
        'seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mapa de progreso de tours del usuario: tour_key => ['version', 'state', 'seen_at'].
     */
    public static function mapFor(User $user): array
    {
        return self::where('user_id', $user->id)
            ->get()
            ->mapWithKeys(fn (self $progress) => [
                $progress->tour_key => [
                    'version' => $progress->version,
                    'state' => $progress->state,
                    'seen_at' => $progress->seen_at?->toISOString(),
                ],
            ])
            ->all();
    }

    /**
     * Registra (upsert) que un usuario vio un tour con una versión determinada.
     */
    public static function record(User $user, string $tourKey, int $version = 1, string $state = 'completed'): self
    {
        return self::updateOrCreate(
            ['user_id' => $user->id, 'tour_key' => $tourKey],
            ['version' => $version, 'state' => $state, 'seen_at' => now()]
        );
    }

    /**
     * Elimina los registros de progreso del usuario cuyos tour_key ya no existen
     * en el set de tours vigentes (tours retirados u obsoletos).
     */
    public static function purge(User $user, array $tourKeys): int
    {
        $tourKeys = array_values(array_unique(array_filter($tourKeys)));

        if (empty($tourKeys)) {
            return 0;
        }

        return self::where('user_id', $user->id)
            ->whereIn('tour_key', $tourKeys)
            ->delete();
    }
}
