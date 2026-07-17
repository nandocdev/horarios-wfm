<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para el estado en tiempo real de los agentes.
 * Esta tabla es UNLOGGED en PostgreSQL para maximizar el rendimiento de escritura.
 */
class AgentRealtimeState extends Model
{
    protected $table = 'agent_realtime_states';

    protected $fillable = [
        'employee_id',
        'external_id',
        'current_state',
        'reason_code',
        'last_changed_at',
        'metadata',
    ];

    protected $casts = [
        'last_changed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public $timestamps = false; // Manejado manualmente o por DB en updated_at

    /**
     * Relación con el empleado.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
