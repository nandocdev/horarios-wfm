<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa un periodo de tiempo intradía aprobado por WFM para un equipo específico.
 * El coordinador del equipo asigna a sus operadores dentro de este bloque autorizado.
 *
 * [RIESGOS]
 * - max_slots debe controlarse mediante lockForUpdate en la acción de asignación para evitar
 *   condiciones de carrera cuando múltiples coordinadores asignan simultáneamente.
 * - Eliminar un periodo con slots ya usados puede dejar actividades huérfanas (nullOnDelete).
 */
class ApprovedIntradayPeriod extends Model
{
    protected $fillable = [
        'team_id',
        'activity_definition_id',
        'date',
        'start_time',
        'end_time',
        'max_slots',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'max_slots' => 'integer',
    ];

    /**
     * Equipo al que pertenece este periodo aprobado.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Definición de la actividad intradía que se realizará en este periodo.
     */
    public function activityDefinition(): BelongsTo
    {
        return $this->belongsTo(ScheduledActivityDefinition::class, 'activity_definition_id');
    }

    /**
     * Actividades intradía asignadas dentro de este periodo aprobado.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(IntradayActivity::class, 'approved_period_id');
    }

    /**
     * Retorna cuántos slots están actualmente ocupados.
     */
    public function usedSlots(): int
    {
        return $this->assignments()->count();
    }

    /**
     * Retorna cuántos slots están disponibles.
     */
    public function availableSlots(): int
    {
        return max(0, $this->max_slots - $this->usedSlots());
    }

    /**
     * Retorna true si el periodo ya alcanzó su capacidad máxima.
     */
    public function isFull(): bool
    {
        return $this->usedSlots() >= $this->max_slots;
    }
}
