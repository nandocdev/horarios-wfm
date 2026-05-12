<?php

declare(strict_types=1);

namespace App\Modules\SupportModule\Models;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'entity_type', 'entity_id', 'action',
        'before', 'after', 'ip_address', 'user_id',
    ];

    protected $casts = [
        'before' => 'json',
        'after' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra un cambio en el audit log.
     *
     * @param  Model  $model  El modelo que cambió
     * @param  string  $action  La acción realizada (created, updated, deleted)
     */
    public static function log(Model $model, string $action): void
    {
        $before = null;
        $after = null;

        if ($action === 'created') {
            $after = $model->toArray();
        } elseif ($action === 'updated') {
            $before = $model->getOriginal();
            $after = $model->toArray();
        } elseif ($action === 'deleted') {
            $before = $model->getOriginal();
        }

        static::create([
            'entity_type' => get_class($model),
            'entity_id' => $model->getKey(),
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'ip_address' => Request::ip(),
            'user_id' => Auth::id(),
        ]);
    }
}
