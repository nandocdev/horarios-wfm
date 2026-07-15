<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TemporalAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'supervisor_id',
        'team_id',
        'start_date',
        'end_date',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function scopeActiveForDate($query, CarbonInterface $date): void
    {
        $query->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString());
    }

    /**
     * Retorna los IDs de empleados que tienen asignacion temporal
     * a un supervisor o equipo en una fecha determinada.
     *
     * @return array<int>
     */
    public static function subordinateIdsFor(int $supervisorEmployeeId, CarbonInterface $date): array
    {
        return self::where('supervisor_id', $supervisorEmployeeId)
            ->activeForDate($date)
            ->pluck('employee_id')
            ->toArray();
    }

    /**
     * Retorna los IDs de supervisores a los que un empleado
     * esta temporalmente asignado en una fecha.
     *
     * @return array<int>
     */
    public static function supervisorIdsFor(int $employeeId, CarbonInterface $date): array
    {
        return self::where('employee_id', $employeeId)
            ->activeForDate($date)
            ->pluck('supervisor_id')
            ->toArray();
    }

    /**
     * Retorna todos los empleados con asignacion temporal activa
     * que reportan a un supervisor en una fecha.
     */
    public static function activeSubordinatesFor(int $supervisorEmployeeId, CarbonInterface $date): Collection
    {
        return Employee::whereIn('id', self::subordinateIdsFor($supervisorEmployeeId, $date))
            ->with('team', 'position')
            ->get();
    }
}
