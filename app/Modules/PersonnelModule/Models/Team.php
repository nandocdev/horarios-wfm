<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use App\Modules\CoreModule\Concerns\Auditable;
use App\Modules\CoreModule\Models\User;
use Database\Factories\Modules\PersonnelModule\Models\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Modelo para Teams (Equipos).
 *
 * Representa los equipos de trabajo.
 */
class Team extends Model
{
    use Auditable, HasFactory;

    protected static function newFactory()
    {
        return TeamFactory::new();
    }

    protected $fillable = [
        'name',
        'description',
        'supervisor_id',
        'is_active',
        'base_schedule_id',
        'cisco_team_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope para filtrar equipos activos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Miembros del equipo.
     */
    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Supervisor del equipo (referencia a users.id según esquema institucional).
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Empleados asignados a este equipo.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Empleados activos en el equipo (a través de miembros).
     *
     * @deprecated Usar employees() si se refiere a la relación directa en la tabla employees.
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            Employee::class,
            TeamMember::class,
            'team_id', // Foreign key on TeamMember table
            'id', // Foreign key on Employee table
            'id', // Local key on Team table
            'employee_id' // Local key on TeamMember table
        )->where('team_members.is_active', true);
    }
}
