<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Modules\GeoModule\Models\Township;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use App\Shared\Contracts\Employees\EmployeeInterface;
use Database\Factories\Modules\PersonnelModule\Models\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Employee extends Model implements EmployeeInterface
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return EmployeeFactory::new();
    }

    protected $fillable = [
        'employee_number', 'username', 'cisco_username', 'first_name', 'last_name', 'email',
        'birth_date', 'gender', 'blood_type', 'phone', 'mobile_phone', 'address',
        'township_id', 'department_id', 'position_id', 'team_id', 'employment_status_id',
        'parent_id', 'user_id', 'hire_date', 'salary', 'is_active', 'is_manager', 'metadata',
        'base_schedule_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
        'is_manager' => 'boolean',
        'metadata' => 'array',
    ];

    // Relaciones Fundacionales
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function township(): BelongsTo
    {
        return $this->belongsTo(Township::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(EmploymentStatus::class);
    }

    // Alias para mantener semántica roadmap/status en consultas eager loading
    public function status(): BelongsTo
    {
        return $this->employmentStatus();
    }

    // Jerarquía Operativa (Adjacency List)
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'parent_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'parent_id');
    }

    /**
     * Obtiene recursivamente todos los IDs de los subordinados (toda la rama descendente).
     * [PERFORMANCE] Usa una CTE recursiva en PostgreSQL para evitar N+1 y Lazy Loading.
     *
     * @return array<int>
     */
    public function getAllSubordinateIds(): array
    {
        $results = DB::select('
            WITH RECURSIVE subordinates_tree AS (
                SELECT id FROM employees WHERE parent_id = ?
                UNION ALL
                SELECT e.id FROM employees e
                INNER JOIN subordinates_tree st ON e.parent_id = st.id
            )
            SELECT id FROM subordinates_tree
        ', [$this->id]);

        return array_map(fn ($row) => (int) $row->id, $results);
    }

    /**
     * Verifica si el empleado tiene derechos de coordinación/gestión.
     * Incluye managers, supervisores oficiales y posiciones delegadas (ID 2).
     */
    public function hasCoordinatorRights(): bool
    {
        if ($this->is_manager) {
            return true;
        }

        // Posiciones especiales con visibilidad de coordinador (Operador Asist. Serv. Aseg. II)
        if ($this->position_id === 2) {
            return true;
        }

        // Si es supervisor de algún equipo
        return Team::where('supervisor_id', $this->id)->exists();
    }

    /**
     * Obtiene los IDs de los equipos que este empleado gestiona (directa o indirectamente).
     */
    public function getManagedTeamIds(): array
    {
        // El rol 'chief' ahora tiene visibilidad global de todos los equipos.
        if ($this->user?->hasRole('chief')) {
            return Team::active()->pluck('id')->toArray();
        }

        $myId = $this->id;
        $subordinateIds = $this->getAllSubordinateIds();
        $allIds = array_merge([$myId], $subordinateIds);

        $teamIds = Team::whereIn('supervisor_id', $allIds)
            ->pluck('id')
            ->toArray();

        // Si tiene derechos de coordinador pero no es supervisor oficial,
        // incluimos su propio equipo para que pueda gestionarlo.
        if ($this->hasCoordinatorRights() && $this->team_id) {
            $teamIds[] = $this->team_id;
        }

        return array_unique($teamIds);
    }

    /**
     * Verifica si el empleado es supervisor de otro (directa o indirectamente).
     */
    public function isSupervisorOf(int $employeeId): bool
    {
        return in_array($employeeId, $this->getAllSubordinateIds());
    }

    // Equipos
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function currentTeamMember()
    {
        return $this->hasOne(TeamMember::class)->where('is_active', true);
    }

    // Detalles del Empleado
    public function positions(): HasMany
    {
        return $this->hasMany(EmployeePosition::class);
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(EmployeeDependent::class);
    }

    public function diseases(): HasMany
    {
        return $this->hasMany(EmployeeDisease::class);
    }

    public function disabilities(): HasMany
    {
        return $this->hasMany(EmployeeDisability::class);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialsAttribute(): string
    {
        $first = $this->first_name ? substr($this->first_name, 0, 1) : '';
        $last = $this->last_name ? substr($this->last_name, 0, 1) : '';

        return strtoupper($first.$last);
    }

    public function getGenderLabelAttribute(): string
    {
        return match ($this->gender) {
            'M' => 'Masculino',
            'F' => 'Femenino',
            'O' => 'Otro',
            default => 'No especificado',
        };
    }

    public function getContractTypeLabelAttribute(): string
    {
        return match ($this->contract_type ?? '') {
            'full_time' => 'Tiempo completo',
            'part_time' => 'Medio tiempo',
            'contract' => 'Contrato',
            'temporary' => 'Temporal',
            default => 'No especificado',
        };
    }

    /**
     * Scope para filtrar empleados activos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->full_name;
    }

    public function getEmployeeNumber(): string
    {
        return $this->employee_number;
    }

    public function getTeamId(): ?int
    {
        return $this->team_id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }
}
